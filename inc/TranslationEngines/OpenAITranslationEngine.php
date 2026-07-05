<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\OpenAIApiHelper;

class OpenAITranslationEngine extends AbstractTranslationEngine
{
    const ENGINE_KEY = 'openai_translate';

    const FIELD_API_KEY = 'openai-api-key';
    const FIELD_MODEL = 'openai-model';
    const FIELD_ENDPOINT = 'openai-endpoint';

    const AVAILABLE_MODELS = [
        'gpt-5.4'     => 'GPT-5.4 (Latest)',
        'gpt-5.2'     => 'GPT-5.2',
        'gpt-5.2-pro' => 'GPT-5.2 Pro',
        'gpt-4.1'     => 'GPT-4.1',
        'gpt-4o-mini'  => 'GPT-4o mini',
    ];

    const AVAILABLE_ENDPOINTS = [
        'responses'        => 'Responses API (recommended)',
        'chat_completions' => 'Chat Completions (legacy)',
    ];

    protected function getApiHelperClass(): string { return OpenAIApiHelper::class; }
    protected function getFilterPrefix(): string   { return 'openai'; }
    protected function getEngineLabel(): string     { return 'OpenAI'; }
    protected function getEmptyKeyMessage(): string { return __('Please enter your OpenAI API key.', 'rankscale-ai-for-translatepress'); }

    public function send_request($source_language, $language_code, $strings_array)
    {
        $systemInstruction = OpenAIApiHelper::getSystemInstruction($language_code, $source_language);
        $userContent       = OpenAIApiHelper::getUserContent($strings_array);

        if ($this->get_endpoint() === 'responses') {
            $data = [
                'model'             => $this->get_model(),
                'instructions'      => $systemInstruction,
                'input'             => $userContent,
                'temperature'       => 0.3,
                'max_output_tokens' => 52000,
            ];
        } else {
            $data = [
                'model'       => $this->get_model(),
                'temperature' => 0.3,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user',   'content' => $userContent],
                ],
                'max_tokens' => 52000,
            ];
        }

        $referer = $this->get_referer();

        return wp_remote_post($this->get_api_url(), [
            'method'  => 'POST',
            'timeout' => 180,
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

        if (isset($data['output_text']) && is_string($data['output_text'])) {
            return $data['output_text'];
        }

        if (isset($data['output']) && is_array($data['output'])) {
            foreach ($data['output'] as $item) {
                if (isset($item['type']) && $item['type'] === 'message' && isset($item['content'])) {
                    foreach ($item['content'] as $block) {
                        if (isset($block['type']) && $block['type'] === 'output_text' && isset($block['text'])) {
                            return $block['text'];
                        }
                    }
                }
            }
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function get_api_key()
    {
        return isset($this->settings['trp_machine_translation_settings'][self::FIELD_API_KEY])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_API_KEY] : false;
    }

    public function get_model()
    {
        $model = isset($this->settings['trp_machine_translation_settings'][self::FIELD_MODEL])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_MODEL] : 'gpt-5.4';
        if (!array_key_exists($model, self::AVAILABLE_MODELS)) {
            $model = 'gpt-5.4';
        }
        return $model;
    }

    public function get_endpoint()
    {
        $endpoint = isset($this->settings['trp_machine_translation_settings'][self::FIELD_ENDPOINT])
            ? $this->settings['trp_machine_translation_settings'][self::FIELD_ENDPOINT] : 'responses';
        if (!array_key_exists($endpoint, self::AVAILABLE_ENDPOINTS)) {
            $endpoint = 'responses';
        }
        return $endpoint;
    }

    public function get_api_url()
    {
        if ($this->get_endpoint() === 'responses') {
            return 'https://api.openai.com/v1/responses';
        }
        return 'https://api.openai.com/v1/chat/completions';
    }
}
