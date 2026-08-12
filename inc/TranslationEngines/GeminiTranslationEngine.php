<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\GeminiApiHelper;

class GeminiTranslationEngine extends AbstractTranslationEngine
{
    const ENGINE_KEY = 'gemini_translate';
    const FIELD_API_KEY = 'gemini-api-key';
    const FIELD_MODEL = 'gemini-model';
    const FIELD_CUSTOM_MODEL = 'gemini-custom-model';

    const DEFAULT_MODEL = 'gemini-3.6-flash';

    const AVAILABLE_MODELS = [
        'gemini-3.6-flash'      => 'Gemini 3.6 Flash (Recommended)',
        'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash-Lite (Cheapest)',
        'gemini-2.5-flash'      => 'Gemini 2.5 Flash (Legacy)',
        'gemini-2.5-pro'        => 'Gemini 2.5 Pro (Legacy)',
        'custom'                => 'Custom model ID…',
    ];

    // Retired API model IDs mapped to their closest living replacement, so
    // settings saved under an older plugin version keep translating after
    // Google renames a model instead of silently 404ing.
    const LEGACY_MODEL_MAP = [
        'gemini-3-flash-preview' => 'gemini-3.6-flash',
        'gemini-2.5-flash-lite'  => 'gemini-3.5-flash-lite',
    ];

    protected function getApiHelperClass(): string { return GeminiApiHelper::class; }
    protected function getFilterPrefix(): string   { return 'gemini'; }
    protected function getEngineLabel(): string     { return 'Gemini'; }
    protected function getEmptyKeyMessage(): string { return __('Please enter your Google Gemini API key. You can get it from Google AI Studio.', 'rankscale-ai-for-translatepress'); }

    public function send_request($source_language, $language_code, $strings_array)
    {
        $systemInstruction = GeminiApiHelper::getSystemInstruction($language_code, $source_language);
        $userContent       = GeminiApiHelper::getUserContent($strings_array);
        $maxOutputTokens   = (int) apply_filters('trp_ai_max_output_tokens', 52000, self::ENGINE_KEY, $this->get_model());
        $data              = GeminiApiHelper::buildRequestBody($userContent, $systemInstruction, $maxOutputTokens);

        $referer = $this->get_referer();

        return wp_remote_post($this->get_api_url(), [
            'method'  => 'POST',
            'timeout' => 120,
            'headers' => [
                'Referer'        => $referer,
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $this->get_api_key(),
            ],
            'body' => json_encode($data),
        ]);
    }

    protected function extractTranslatedContent($responseBody): string
    {
        $data = json_decode($responseBody, true);
        if (!is_array($data) || !empty($data['error'])) {
            return '';
        }
        if (empty($data['candidates'])) {
            $reason = $data['promptFeedback']['blockReason'] ?? 'unknown';
            error_log(sprintf('[Rankscale AI] Gemini: empty candidates, reason=%s', $reason));
            return '';
        }
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $reason = $data['candidates'][0]['finishReason'] ?? 'unknown';
            error_log(sprintf('[Rankscale AI] Gemini: no text in candidate, finishReason=%s', $reason));
            return '';
        }
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    public function get_api_key()
    {
        return isset($this->settings['trp_machine_translation_settings'][self::FIELD_API_KEY])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_API_KEY] : false;
    }

    public function get_model()
    {
        $mt_settings = $this->settings['trp_machine_translation_settings'] ?? [];
        $model = isset($mt_settings[self::FIELD_MODEL]) ? $mt_settings[self::FIELD_MODEL] : self::DEFAULT_MODEL;

        if ($model === 'custom') {
            $custom = isset($mt_settings[self::FIELD_CUSTOM_MODEL]) ? trim($mt_settings[self::FIELD_CUSTOM_MODEL]) : '';
            $model  = ($custom !== '') ? $custom : self::DEFAULT_MODEL;
        }

        if (isset(self::LEGACY_MODEL_MAP[$model])) {
            $model = self::LEGACY_MODEL_MAP[$model];
        }

        // Model ID lands in the request URL path; anything outside this charset falls back.
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $model)) {
            $model = self::DEFAULT_MODEL;
        }

        return $model;
    }

    public function get_api_url()
    {
        $model = $this->get_model();
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }
}
