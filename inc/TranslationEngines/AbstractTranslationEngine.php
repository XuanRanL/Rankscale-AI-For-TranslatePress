<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\ResultAssembler;
use Rankscale\TranslatePress\AI\Helpers\RetryHelper;
use TRP_Machine_Translator;
use WP_Error;

abstract class AbstractTranslationEngine extends TRP_Machine_Translator
{
    /**
     * Each child MUST override this with its own API call logic.
     * Not declared abstract to avoid conflict if TRP adds a concrete version.
     *
     * @param string $source_language
     * @param string $language_code
     * @param array $strings_array
     * @return array|WP_Error
     */
    public function send_request($source_language, $language_code, $strings_array)
    {
        return new WP_Error('not_implemented', 'send_request() must be overridden by the child engine class.');
    }

    /**
     * Extract the translated text content from a raw API response body.
     * @param string $responseBody
     * @return string
     */
    abstract protected function extractTranslatedContent($responseBody): string;

    /** @return string  Fully-qualified ApiHelper class name */
    abstract protected function getApiHelperClass(): string;

    /** @return string  Filter prefix, e.g. 'deepseek', 'gemini', 'openai' */
    abstract protected function getFilterPrefix(): string;

    /** @return string  Human-readable engine label for logs */
    abstract protected function getEngineLabel(): string;

    /** @return string  Translated error message shown when API key is empty */
    abstract protected function getEmptyKeyMessage(): string;

    /**
     * True when the service stopped writing because it reached its output limit.
     * Engines override this with their own "finish reason" field.
     *
     * @param string $responseBody
     * @return bool
     */
    protected function isTruncatedResponse($responseBody): bool
    {
        return false;
    }

    /**
     * Strings per request. TranslatePress 3.x cuts the page's strings into chunks of
     * this size, saves each chunk as soon as it is translated and checks its time
     * budget between two chunks. Returning our own size here makes one TranslatePress
     * chunk equal one API call; with the parent's default of 50 a single chunk became
     * up to three calls in a row and the page waited for all of them.
     *
     * @return int
     */
    public function get_chunk_size()
    {
        $chunk_size = (int) apply_filters('trp_ai_chunk_size', 20, static::ENGINE_KEY);
        return max(1, min(50, $chunk_size));
    }

    /**
     * Seconds to wait for one API call.
     *
     * @param int $default
     * @return int
     */
    protected function get_request_timeout($default)
    {
        $timeout = (int) apply_filters('trp_ai_request_timeout', (int) $default, static::ENGINE_KEY);
        return max(5, $timeout);
    }

    /**
     * The instruction sent with every request. The filter lets a site add its own
     * context (what the site sells, names that must stay untranslated, tone) without
     * editing the plugin.
     *
     * @param string $target_language
     * @param string $source_language
     * @return string
     */
    protected function get_system_instruction($target_language, $source_language)
    {
        $helperClass = $this->getApiHelperClass();
        $instruction = $helperClass::getSystemInstruction($target_language, $source_language);
        $filtered    = apply_filters('trp_ai_system_instruction', $instruction, static::ENGINE_KEY, $target_language, $source_language);

        return (is_string($filtered) && $filtered !== '') ? $filtered : $instruction;
    }

    /**
     * How long translation stops after repeated failures, in seconds. A failed request
     * no longer stores anything, so TranslatePress asks again on every page load; without
     * a pause a service that is down, out of credit or refusing the key would be called
     * (and waited for) on every page view. Return 0 from the filter to never pause.
     *
     * @param int $code HTTP status of the failed request, 0 when there was no answer.
     * @return int
     */
    protected function get_failure_pause($code)
    {
        return max(0, (int) apply_filters('trp_ai_failure_pause', 600, static::ENGINE_KEY, (int) $code));
    }

    protected function is_paused()
    {
        return (bool) get_transient('trp_ai_pause_' . static::ENGINE_KEY);
    }

    /**
     * Count failed requests in a row and pause after the third one; pause at once when the
     * service refuses the key, asks for payment or asks to slow down.
     *
     * @param bool $ok   True when the request returned at least one usable translation.
     * @param int  $code HTTP status, 0 when there was no answer.
     * @return bool True when translation is paused now.
     */
    protected function record_request_outcome($ok, $code = 0)
    {
        $fails_key = 'trp_ai_fails_' . static::ENGINE_KEY;

        if ($ok) {
            if (false !== get_transient($fails_key)) {
                delete_transient($fails_key);
            }
            return false;
        }

        $pause = $this->get_failure_pause($code);
        if ($pause < 1) {
            return false;
        }

        $fails = (int) get_transient($fails_key) + 1;
        if ($fails < 3 && !in_array((int) $code, [401, 402, 403, 429], true)) {
            set_transient($fails_key, $fails, $pause);
            return false;
        }

        delete_transient($fails_key);
        set_transient('trp_ai_pause_' . static::ENGINE_KEY, (int) $code > 0 ? (int) $code : 1, $pause);
        error_log(sprintf('[Rankscale AI] %s: translation paused for %d seconds after failed requests (last status %d)', $this->getEngineLabel(), $pause, (int) $code));

        return true;
    }

    /**
     * Requests to send for these strings, keys preserved. A text that carries numbered
     * lines of its own goes alone: in a shared request its lines would be read as the
     * next items and every later translation would land on the wrong string.
     *
     * @param array $strings
     * @return array[]
     */
    protected function build_chunks(array $strings)
    {
        $helperClass = $this->getApiHelperClass();
        $shared      = [];
        $alone       = [];

        foreach ($strings as $key => $string) {
            if ($helperClass::hasNumberedLines($string)) {
                $alone[] = [$key => $string];
            } else {
                $shared[$key] = $string;
            }
        }

        return array_merge(array_chunk($shared, $this->get_chunk_size(), true), $alone);
    }

    public function translate_array($new_strings, $target_language_code, $source_language_code)
    {
        if ($source_language_code == null)
            $source_language_code = $this->settings['default-language'] ?? '';

        if (empty($new_strings) || !$this->verify_request_parameters($target_language_code, $source_language_code))
            return [];

        // Paused after repeated failures: the strings stay untranslated for now.
        if ($this->is_paused())
            return [];

        $translated_strings = [];
        $prefix = $this->getFilterPrefix();
        $label  = $this->getEngineLabel();

        $source_language = apply_filters("trp_{$prefix}_source_language", $this->machine_translation_codes[$source_language_code] ?? $source_language_code, $source_language_code, $target_language_code);
        $target_language = apply_filters("trp_{$prefix}_target_language", $this->machine_translation_codes[$target_language_code] ?? $target_language_code, $source_language_code, $target_language_code);

        $new_strings_chunks = $this->build_chunks($new_strings);

        foreach ($new_strings_chunks as $new_strings_chunk) {

            $response = RetryHelper::withRetry(function () use ($source_language, $target_language, $new_strings_chunk) {
                return $this->send_request($source_language, $target_language, $new_strings_chunk);
            });

            $log_response = $response;
            if (is_array($log_response) && isset($log_response['body']) && is_string($log_response['body']) && strlen($log_response['body']) > 2000) {
                $log_response['body'] = mb_substr($log_response['body'], 0, 2000, 'UTF-8') . '...[truncated]';
            }
            $this->machine_translator_logger->log([
                'strings'     => serialize($new_strings_chunk),
                'response'    => serialize($log_response),
                'lang_source' => $source_language,
                'lang_target' => $target_language,
            ]);

            $is_success = is_array($response) && !is_wp_error($response)
                && isset($response['response']['code'])
                && (int) $response['response']['code'] === 200;

            // A failed call returns nothing for its strings. TranslatePress then leaves
            // them untranslated and asks again on a later page load. Returning the source
            // text here would be saved as the "machine translation" and never retried.
            if (!$is_success) {
                if (is_wp_error($response)) {
                    error_log("[Rankscale AI] {$label} WP_Error: " . $response->get_error_message());
                } elseif (is_array($response) && isset($response['response']['code'])) {
                    $body_excerpt = isset($response['body']) ? mb_substr((string) $response['body'], 0, 500) : '';
                    error_log(sprintf("[Rankscale AI] {$label} HTTP %d: %s", $response['response']['code'], $body_excerpt));
                }
                $failed_code = (is_array($response) && isset($response['response']['code'])) ? (int) $response['response']['code'] : 0;
                if ($this->record_request_outcome(false, $failed_code))
                    break;
                continue;
            }

            $translatedContent = $this->extractTranslatedContent($response['body']);
            if (!is_string($translatedContent) || $translatedContent === '') {
                error_log(sprintf('[Rankscale AI] %s: answer without translated text for %d strings, nothing saved', $label, count($new_strings_chunk)));
                if ($this->record_request_outcome(false, 200))
                    break;
                continue;
            }

            $helperClass = $this->getApiHelperClass();
            $sent_alone  = count($new_strings_chunk) === 1 && $helperClass::hasNumberedLines(reset($new_strings_chunk));
            $parsed      = $helperClass::parseNumberedAnswer($translatedContent, count($new_strings_chunk), $sent_alone);
            $result      = ResultAssembler::assemble($new_strings_chunk, $parsed, $this->isTruncatedResponse($response['body']));

            if ($result['status'] !== ResultAssembler::STATUS_OK) {
                error_log(sprintf(
                    '[Rankscale AI] %s: %d of %d strings saved, the rest will be requested again (%s)',
                    $label, $result['received'], $result['expected'], $result['reason']
                ));
            }

            foreach ($result['translations'] as $key => $translation) {
                $translated_strings[$key] = $this->sanitize_injected_markup($new_strings_chunk[$key], $translation);
            }

            // The call was answered, so it counts towards TranslatePress's own character limit.
            $this->machine_translator_logger->count_towards_quota($new_strings_chunk);

            // An answer nothing could be taken from counts as a failed request.
            if ($this->record_request_outcome(!empty($result['translations']), 200))
                break;

            if ($this->machine_translator_logger->quota_exceeded())
                break;
        }

        return $translated_strings;
    }

    /**
     * @return array|WP_Error
     */
    public function test_request()
    {
        return $this->send_request('en', 'es', ['Where are you from?', 'I love you!']);
    }

    public function check_api_key_validity()
    {
        $translation_engine = $this->settings['trp_machine_translation_settings']['translation-engine'] ?? '';
        $api_key = $this->get_api_key();

        $is_error       = false;
        $return_message = '';

        if (static::ENGINE_KEY === $translation_engine
            && ($this->settings['trp_machine_translation_settings']['machine-translation'] ?? '') === 'yes'
        ) {
            if (isset($this->correct_api_key)) {
                return $this->correct_api_key;
            }

            if (empty($api_key)) {
                $is_error       = true;
                $return_message = $this->getEmptyKeyMessage();
            } elseif (strlen($api_key) < 10) {
                $is_error       = true;
                $return_message = __('The API key appears to be invalid (too short).', 'rankscale-ai-for-translatepress');
            }

            $this->correct_api_key = [
                'message' => $return_message,
                'error'   => $is_error,
            ];
        }

        return [
            'message' => $return_message,
            'error'   => $is_error,
        ];
    }

    public function get_supported_languages()
    {
        $helperClass = $this->getApiHelperClass();
        return array_keys($helperClass::supportedLanguages);
    }

    public function get_engine_specific_language_codes($languages)
    {
        $codes = [];
        foreach ($languages as $lang) {
            $mapped = $this->machine_translation_codes[$lang] ?? $lang;
            $mapped = apply_filters("trp_{$this->getFilterPrefix()}_target_language", $mapped, $this->settings['default-language'] ?? '', $lang);
            $codes[] = $mapped;
        }
        return array_unique($codes);
    }

    protected function sanitize_injected_markup($original, $translated)
    {
        $original_has_html = (strpos($original, '<') !== false) || (strpos($original, '>') !== false);
        if (!$original_has_html && preg_match('/<\/?[a-zA-Z!][^>]*>/u', $translated)) {
            $translated = str_replace(['<', '>'], ['&lt;', '&gt;'], $translated);
        }
        return $translated;
    }
}
