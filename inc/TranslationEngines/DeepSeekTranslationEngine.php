<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\DeepSeekApiHelper;

class DeepSeekTranslationEngine extends AbstractTranslationEngine
{
    const ENGINE_KEY = 'deepseek_translate';

    const FIELD_API_KEY = 'deepseek-api-key';
    const FIELD_API_ENDPOINT = 'deepseek-api-endpoint';
    const FIELD_CUSTOM_API_URL = 'deepseek-custom-api-url';
    const FIELD_ENABLE_THINKING = 'deepseek-enable-thinking';
    const FIELD_THINKING_BUDGET = 'deepseek-thinking-budget';

    const AVAILABLE_ENDPOINTS = [
        'siliconflow' => 'SiliconFlow',
        'deepseek'    => 'DeepSeek Official',
        'custom'      => 'Custom URL',
    ];

    protected function getApiHelperClass(): string { return DeepSeekApiHelper::class; }
    protected function getFilterPrefix(): string   { return 'deepseek'; }
    protected function getEngineLabel(): string     { return 'DeepSeek'; }
    protected function getEmptyKeyMessage(): string { return __('Please enter your DeepSeek API key.', 'rankscale-ai-for-translatepress'); }

    public function send_request($source_language, $language_code, $strings_array)
    {
        $systemInstruction = DeepSeekApiHelper::getSystemInstruction($language_code, $source_language);
        $userContent       = DeepSeekApiHelper::getUserContent($strings_array);

        $enable_thinking = $this->get_enable_thinking();

        $data = [
            'model'           => $this->get_model_name(),
            'temperature'     => 0.3,
            'messages'        => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user',   'content' => $userContent],
            ],
            'max_tokens'      => 52000,
            'enable_thinking' => $enable_thinking,
        ];

        if ($enable_thinking) {
            $data['thinking_budget'] = $this->get_thinking_budget();
        }

        $referer = $this->get_referer();
        $timeout = $enable_thinking ? 300 : 180;

        return wp_remote_post($this->get_api_url(), [
            'method'  => 'POST',
            'timeout' => $timeout,
            'headers' => [
                'Referer'       => $referer,
                'Authorization' => 'Bearer ' . $this->get_api_key(),
                'Content-Type'  => 'application/json',
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
        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function get_model_name()
    {
        switch ($this->get_endpoint()) {
            case 'deepseek':
                return 'deepseek-chat';
            default:
                return 'deepseek-ai/DeepSeek-V3.2';
        }
    }

    public function get_api_key()
    {
        return isset($this->settings['trp_machine_translation_settings'][self::FIELD_API_KEY])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_API_KEY] : false;
    }

    public function get_api_url()
    {
        $endpoint = $this->get_endpoint();
        switch ($endpoint) {
            case 'deepseek':
                return 'https://api.deepseek.com/v1/chat/completions';
            case 'custom':
                $custom_url = isset($this->settings['trp_machine_translation_settings'][self::FIELD_CUSTOM_API_URL])
                    ? trim($this->settings['trp_machine_translation_settings'][self::FIELD_CUSTOM_API_URL]) : '';
                return $custom_url !== '' ? $custom_url : 'https://api.siliconflow.cn/v1/chat/completions';
            default:
                return 'https://api.siliconflow.cn/v1/chat/completions';
        }
    }

    public function get_endpoint()
    {
        $endpoint = isset($this->settings['trp_machine_translation_settings'][self::FIELD_API_ENDPOINT])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_API_ENDPOINT] : 'siliconflow';
        if (!array_key_exists($endpoint, self::AVAILABLE_ENDPOINTS)) {
            $endpoint = 'siliconflow';
        }
        return $endpoint;
    }

    public function get_enable_thinking()
    {
        $val = isset($this->settings['trp_machine_translation_settings'][self::FIELD_ENABLE_THINKING])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_ENABLE_THINKING] : 'no';
        return $val === 'yes';
    }

    public function get_thinking_budget()
    {
        $budget = isset($this->settings['trp_machine_translation_settings'][self::FIELD_THINKING_BUDGET])
            ? (int) $this->settings['trp_machine_translation_settings'][self::FIELD_THINKING_BUDGET] : 4096;
        return max(128, min(32768, $budget));
    }
}
