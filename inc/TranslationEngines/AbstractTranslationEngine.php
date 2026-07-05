<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

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

    public function translate_array($new_strings, $target_language_code, $source_language_code)
    {
        if ($source_language_code == null)
            $source_language_code = $this->settings['default-language'] ?? '';

        if (empty($new_strings) || !$this->verify_request_parameters($target_language_code, $source_language_code))
            return [];

        $translated_strings = [];
        $prefix = $this->getFilterPrefix();

        $source_language = apply_filters("trp_{$prefix}_source_language", $this->machine_translation_codes[$source_language_code] ?? $source_language_code, $source_language_code, $target_language_code);
        $target_language = apply_filters("trp_{$prefix}_target_language", $this->machine_translation_codes[$target_language_code] ?? $target_language_code, $source_language_code, $target_language_code);

        $chunk_size = (int) apply_filters('trp_ai_chunk_size', 20, static::ENGINE_KEY);
        $chunk_size = max(1, min(50, $chunk_size));
        $new_strings_chunks = array_chunk($new_strings, $chunk_size, true);

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

            if (is_array($response) && !is_wp_error($response)
                && isset($response['response']['code'])
                && (int) $response['response']['code'] === 200
            ) {
                $translatedContent = $this->extractTranslatedContent($response['body']);

                if (is_string($translatedContent) && $translatedContent !== '') {
                    $helperClass  = $this->getApiHelperClass();
                    $translations = $helperClass::parseTranslatedItems($translatedContent, count($new_strings_chunk));

                    $this->validate_translation_quality('', '', count($new_strings_chunk), count($translations));

                    $i = 0;
                    foreach ($new_strings_chunk as $key => $old_string) {
                        if (isset($translations[$i]) && $translations[$i] !== '') {
                            $translated_strings[$key] = $this->sanitize_injected_markup($old_string, $translations[$i]);
                        } else {
                            $translated_strings[$key] = $old_string;
                        }
                        $i++;
                    }

                    $this->machine_translator_logger->count_towards_quota($new_strings_chunk);
                } else {
                    foreach ($new_strings_chunk as $key => $old_string) {
                        $translated_strings[$key] = $old_string;
                    }
                }

                if ($this->machine_translator_logger->quota_exceeded())
                    break;

            } else {
                $label = $this->getEngineLabel();
                if (is_wp_error($response)) {
                    error_log("[Rankscale AI] {$label} WP_Error: " . $response->get_error_message());
                } elseif (is_array($response) && isset($response['response']['code'])) {
                    $body_excerpt = isset($response['body']) ? mb_substr($response['body'], 0, 500) : '';
                    error_log(sprintf("[Rankscale AI] {$label} HTTP %d: %s", $response['response']['code'], $body_excerpt));
                }

                foreach ($new_strings_chunk as $key => $old_string) {
                    $translated_strings[$key] = $old_string;
                }
            }
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

    protected function validate_translation_quality($original, $translated, $expectedCount, $actualCount)
    {
        if ($actualCount < $expectedCount * 0.5) {
            error_log(sprintf(
                '[Rankscale AI] %s: quality warning - only %d/%d items translated',
                $this->getEngineLabel(), $actualCount, $expectedCount
            ));
        }
    }
}
