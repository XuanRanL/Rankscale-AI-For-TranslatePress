<?php
namespace Rankscale\TranslatePress\AI\TranslationEngines;

use Rankscale\TranslatePress\AI\Helpers\OpenAIApiHelper;

class OpenAITranslationEngine extends AbstractTranslationEngine
{
    const ENGINE_KEY = 'openai_translate';

    const FIELD_API_KEY = 'openai-api-key';
    const FIELD_MODEL = 'openai-model';
    const FIELD_CUSTOM_MODEL = 'openai-custom-model';
    const FIELD_ENDPOINT = 'openai-endpoint';

    const DEFAULT_MODEL = 'gpt-5.4';

    const AVAILABLE_MODELS = [
        'gpt-5.4'     => 'GPT-5.4 (Latest)',
        'gpt-5.2'     => 'GPT-5.2',
        'gpt-5.2-pro' => 'GPT-5.2 Pro',
        'gpt-4.1'     => 'GPT-4.1',
        'gpt-4o-mini' => 'GPT-4o mini',
        'custom'      => 'Custom model ID…',
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
        $systemInstruction = $this->get_system_instruction($language_code, $source_language);
        $userContent       = OpenAIApiHelper::getUserContent($strings_array);

        $model     = $this->get_model();
        $maxOutput = $this->get_max_output_tokens($model);

        if ($this->get_endpoint() === 'responses') {
            $data = [
                'model'             => $model,
                'instructions'      => $systemInstruction,
                'input'             => $userContent,
                'max_output_tokens' => $maxOutput,
            ];
        } else {
            // max_completion_tokens replaced max_tokens for all current chat models;
            // max_tokens gets a hard 400 from the GPT-5 family.
            $data = [
                'model'                 => $model,
                'messages'              => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user',   'content' => $userContent],
                ],
                'max_completion_tokens' => $maxOutput,
            ];
        }

        // Reasoning models (gpt-5.x, o-series) reject any temperature other than
        // the default 1 with HTTP 400, so only non-reasoning models get 0.3.
        if (!$this->is_reasoning_model($model)) {
            $data['temperature'] = 0.3;
        }

        $referer = $this->get_referer();

        return wp_remote_post($this->get_api_url(), [
            'method'  => 'POST',
            'timeout' => $this->get_request_timeout(180),
            'headers' => [
                'Referer'       => $referer,
                'Authorization' => 'Bearer ' . $this->get_api_key(),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($data),
        ]);
    }

    protected function isTruncatedResponse($responseBody): bool
    {
        $data = json_decode($responseBody, true);
        if (!is_array($data)) {
            return false;
        }

        // Responses API
        if (($data['status'] ?? '') === 'incomplete') {
            return ($data['incomplete_details']['reason'] ?? '') === 'max_output_tokens';
        }

        // Chat Completions
        return ($data['choices'][0]['finish_reason'] ?? '') === 'length';
    }

    protected function is_reasoning_model($model)
    {
        return (bool) preg_match('/^(gpt-5|o\d)/i', $model);
    }

    /**
     * Requesting more output tokens than a model supports is a hard 400,
     * so cap per model family instead of one blanket value.
     */
    protected function get_max_output_tokens($model)
    {
        if (strpos($model, 'gpt-4o-mini') === 0) {
            $cap = 16384;
        } elseif (strpos($model, 'gpt-4.1') === 0) {
            $cap = 32768;
        } elseif ($this->is_reasoning_model($model)) {
            $cap = 52000;
        } else {
            // Unknown custom model: stay under the smallest common limit.
            $cap = 16384;
        }
        return (int) apply_filters('trp_ai_max_output_tokens', $cap, self::ENGINE_KEY, $model);
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
                if (isset($item['type']) && $item['type'] === 'message' && isset($item['content']) && is_array($item['content'])) {
                    foreach ($item['content'] as $block) {
                        if (isset($block['type']) && $block['type'] === 'output_text' && isset($block['text']) && is_string($block['text'])) {
                            return $block['text'];
                        }
                    }
                }
            }
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        return is_string($content) ? $content : '';
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

        // The model ID travels in the JSON body; fine-tuned models are named "ft:gpt-…:org::id".
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]*$/', $model)) {
            $model = self::DEFAULT_MODEL;
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
