<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\GeminiApiHelper;

class GeminiTranslationEngine extends AbstractTranslationEngine
{
    const ENGINE_KEY = 'gemini_translate';
    const FIELD_API_KEY = 'gemini-api-key';
    const FIELD_MODEL = 'gemini-model';

    const AVAILABLE_MODELS = [
        'gemini-2.5-flash'       => 'Gemini 2.5 Flash (Fast & Efficient)',
        'gemini-2.5-flash-lite'  => 'Gemini 2.5 Flash-Lite (Cheapest)',
        'gemini-2.5-pro'         => 'Gemini 2.5 Pro (Best Quality)',
        'gemini-3-flash-preview' => 'Gemini 3 Flash (Preview - Frontier)',
    ];

    protected function getApiHelperClass(): string { return GeminiApiHelper::class; }
    protected function getFilterPrefix(): string   { return 'gemini'; }
    protected function getEngineLabel(): string     { return 'Gemini'; }
    protected function getEmptyKeyMessage(): string { return __('Please enter your Google Gemini API key. You can get it from Google AI Studio.', 'rankscale-ai-for-translatepress'); }

    public function send_request($source_language, $language_code, $strings_array)
    {
        $systemInstruction = GeminiApiHelper::getSystemInstruction($language_code, $source_language);
        $userContent       = GeminiApiHelper::getUserContent($strings_array);
        $data              = GeminiApiHelper::buildRequestBody($userContent, 0.3, $systemInstruction);

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
        $model = isset($this->settings['trp_machine_translation_settings'][self::FIELD_MODEL])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_MODEL] : 'gemini-2.5-flash';
        if (!array_key_exists($model, self::AVAILABLE_MODELS)) {
            $model = 'gemini-2.5-flash';
        }
        return $model;
    }

    public function get_api_url()
    {
        $model = $this->get_model();
        return "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
    }
}
