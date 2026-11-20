<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\DeepSeekApiHelper;

class DeepSeekTranslationEngine extends AbstractTranslationEngine
{
    const ENGINE_KEY = 'deepseek_translate';

    const FIELD_API_KEY = 'deepseek-api-key';
    const FIELD_API_ENDPOINT = 'deepseek-api-endpoint';
    const FIELD_CUSTOM_API_URL = 'deepseek-custom-api-url';
    const FIELD_CUSTOM_MODEL = 'deepseek-custom-model';
    const FIELD_ENABLE_THINKING = 'deepseek-enable-thinking';
    const FIELD_THINKING_BUDGET = 'deepseek-thinking-budget';
    const FIELD_REASONING_EFFORT = 'deepseek-reasoning-effort';

    const AVAILABLE_ENDPOINTS = [
        'siliconflow' => 'SiliconFlow',
        'deepseek'    => 'DeepSeek Official',
        'custom'      => 'Custom URL',
    ];

    // OpenAI-style `reasoning_effort` values for the custom endpoint. The empty value
    // sends no `reasoning_effort` and keeps the thinking switch below in charge.
    const REASONING_EFFORTS = ['', 'none', 'minimal', 'low', 'medium', 'high', 'xhigh'];

    protected function getApiHelperClass(): string { return DeepSeekApiHelper::class; }
    protected function getFilterPrefix(): string   { return 'deepseek'; }
    protected function getEngineLabel(): string     { return 'DeepSeek'; }
    protected function getEmptyKeyMessage(): string { return __('Please enter your DeepSeek API key.', 'rankscale-ai-for-translatepress'); }

    public function send_request($source_language, $language_code, $strings_array)
    {
        $endpoint = $this->get_endpoint();
        $model    = $this->get_model_name();

        $data = [
            'model'    => $model,
            'messages' => [
                ['role' => 'system', 'content' => $this->get_system_instruction($language_code, $source_language)],
                ['role' => 'user',   'content' => DeepSeekApiHelper::getUserContent($strings_array)],
            ],
        ];

        // Return null from the filter to leave the temperature out (some reasoning
        // models accept only their default).
        $temperature = apply_filters('trp_ai_temperature', 0.3, self::ENGINE_KEY, $model);
        if (is_numeric($temperature)) {
            $data['temperature'] = (float) $temperature;
        }

        // A service that refuses this limit (HTTP 400 "max_tokens is too large") can be
        // given a smaller one through the filter; 0 sends no limit at all.
        $max_tokens = (int) apply_filters('trp_ai_max_output_tokens', 52000, self::ENGINE_KEY, $model);
        if ($max_tokens > 0) {
            $data['max_tokens'] = $max_tokens;
        }

        $enable_thinking  = $this->get_enable_thinking();
        $reasoning_effort = ($endpoint === 'custom') ? $this->get_reasoning_effort() : '';
        $is_thinking      = $enable_thinking;

        if ($reasoning_effort !== '') {
            // OpenAI-compatible gateways take `reasoning_effort`; they ignore or refuse
            // SiliconFlow's `enable_thinking`, so only one of the two is sent.
            $data['reasoning_effort'] = $reasoning_effort;
            $is_thinking = !in_array($reasoning_effort, ['none', 'minimal', 'low'], true);
        } elseif ($endpoint !== 'deepseek') {
            // enable_thinking/thinking_budget are SiliconFlow request fields; the
            // DeepSeek official API uses a different mechanism and may reject them.
            $data['enable_thinking'] = $enable_thinking;
            if ($enable_thinking) {
                $data['thinking_budget'] = $this->get_thinking_budget();
            }
        }

        return wp_remote_post($this->get_api_url(), [
            'method'  => 'POST',
            'timeout' => $this->get_request_timeout($is_thinking ? 300 : 180),
            'headers' => [
                'Referer'       => $this->get_referer(),
                'Authorization' => 'Bearer ' . $this->get_api_key(),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($data),
        ]);
    }

    protected function extractTranslatedContent($responseBody): string
    {
        $data = json_decode($responseBody, true);
        if (!is_array($data) || !empty($data['error'])) {
            return '';
        }
        $content = $data['choices'][0]['message']['content'] ?? '';
        return is_string($content) ? $content : '';
    }

    protected function isTruncatedResponse($responseBody): bool
    {
        $data = json_decode($responseBody, true);
        return is_array($data) && (($data['choices'][0]['finish_reason'] ?? '') === 'length');
    }

    public function get_model_name()
    {
        $custom = isset($this->settings['trp_machine_translation_settings'][self::FIELD_CUSTOM_MODEL])
            ? trim($this->settings['trp_machine_translation_settings'][self::FIELD_CUSTOM_MODEL]) : '';
        // The model ID travels in the JSON body. ':' is part of real IDs (fine-tuned
        // models, "name:tag" on local gateways).
        if ($custom !== '' && preg_match('#^[A-Za-z0-9][A-Za-z0-9._/:-]*$#', $custom)) {
            return $custom;
        }

        switch ($this->get_endpoint()) {
            case 'deepseek':
                // 'deepseek-chat' was hard-retired by DeepSeek on 2026-07-24.
                return 'deepseek-v4-flash';
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

    /**
     * @return string  One of REASONING_EFFORTS; empty = not set.
     */
    public function get_reasoning_effort()
    {
        $effort = isset($this->settings['trp_machine_translation_settings'][self::FIELD_REASONING_EFFORT])
            ? (string) $this->settings['trp_machine_translation_settings'][self::FIELD_REASONING_EFFORT] : '';
        return in_array($effort, self::REASONING_EFFORTS, true) ? $effort : '';
    }
}
