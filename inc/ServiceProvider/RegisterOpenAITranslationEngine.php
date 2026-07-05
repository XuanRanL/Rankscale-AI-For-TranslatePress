<?php
namespace Rankscale\TranslatePress\AI\ServiceProvider;

use Rankscale\TranslatePress\AI\TranslationEngines\OpenAITranslationEngine;

class RegisterOpenAITranslationEngine extends AbstractRegisterEngine
{
    protected function getEngineKey(): string    { return OpenAITranslationEngine::ENGINE_KEY; }
    protected function getEngineClass(): string  { return OpenAITranslationEngine::class; }
    protected function getEngineLabel(): string  { return 'OpenAI (GPT)'; }
    protected function getFilterPrefix(): string { return 'openai'; }
    protected function getApiKeyField(): string  { return OpenAITranslationEngine::FIELD_API_KEY; }

    protected function renderEngineSettings($settings, $machine_translator, $show_errors, $error_message, $text_input_classes): void
    {
        ?>
        <span class="trp-primary-text-bold"><?php esc_html_e('OpenAI API Key', 'rankscale-ai-for-translatepress'); ?></span>

        <div class="trp-automatic-translation-api-key-container">
            <input type="password" id="trp-openai-api-key"
                   class="<?php echo esc_attr(implode(' ', $text_input_classes)); ?>"
                   name="trp_machine_translation_settings[<?php echo esc_attr(OpenAITranslationEngine::FIELD_API_KEY); ?>]"
                   value="<?php if (!empty($settings[OpenAITranslationEngine::FIELD_API_KEY])) echo esc_attr($settings[OpenAITranslationEngine::FIELD_API_KEY]); ?>"/>
            <?php
            if ($machine_translator && method_exists($machine_translator, 'automatic_translation_svg_output')) {
                $machine_translator->automatic_translation_svg_output($show_errors);
            }
            ?>
        </div>

        <?php if ($show_errors) : ?>
            <span class="trp-error-inline trp-settings-error-text"><?php echo wp_kses_post($error_message); ?></span>
        <?php endif; ?>

        <span class="trp-description-text">
            <?php
            $text = __('Get your API key from <a href="%s" target="_blank">OpenAI</a>.', 'rankscale-ai-for-translatepress');
            echo wp_kses(sprintf($text, 'https://platform.openai.com/api-keys'), ['a' => ['href' => [], 'target' => []]]);
            ?>
        </span>

        <div style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('OpenAI Endpoint', 'rankscale-ai-for-translatepress'); ?></span>
            <select id="trp-openai-endpoint"
                    name="trp_machine_translation_settings[<?php echo esc_attr(OpenAITranslationEngine::FIELD_ENDPOINT); ?>]"
                    class="trp-select" style="margin-top: 5px;">
                <?php
                $selected_endpoint = $settings[OpenAITranslationEngine::FIELD_ENDPOINT] ?? 'responses';
                foreach (OpenAITranslationEngine::AVAILABLE_ENDPOINTS as $endpoint_key => $endpoint_label) { ?>
                    <option value="<?php echo esc_attr($endpoint_key); ?>" <?php selected($selected_endpoint, $endpoint_key); ?>>
                        <?php echo esc_html($endpoint_label); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="trp-description-text"><?php esc_html_e('If your model fails on Responses API, switch to Chat Completions.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>

        <div style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('OpenAI Model', 'rankscale-ai-for-translatepress'); ?></span>
            <select id="trp-openai-model"
                    name="trp_machine_translation_settings[<?php echo esc_attr(OpenAITranslationEngine::FIELD_MODEL); ?>]"
                    class="trp-select" style="margin-top: 5px;">
                <?php
                $selected_model = $settings[OpenAITranslationEngine::FIELD_MODEL] ?? 'gpt-5.4';
                foreach (OpenAITranslationEngine::AVAILABLE_MODELS as $model_key => $model_label) { ?>
                    <option value="<?php echo esc_attr($model_key); ?>" <?php selected($selected_model, $model_key); ?>>
                        <?php echo esc_html($model_label); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="trp-description-text"><?php esc_html_e('Choose the OpenAI model for translations.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>
        <?php
    }

    protected function sanitizeEngineSettings($settings, $mt_settings): array
    {
        if (!empty($mt_settings[OpenAITranslationEngine::FIELD_ENDPOINT]))
            $settings[OpenAITranslationEngine::FIELD_ENDPOINT] = sanitize_text_field($mt_settings[OpenAITranslationEngine::FIELD_ENDPOINT]);

        if (!empty($mt_settings[OpenAITranslationEngine::FIELD_MODEL]))
            $settings[OpenAITranslationEngine::FIELD_MODEL] = sanitize_text_field($mt_settings[OpenAITranslationEngine::FIELD_MODEL]);

        return $settings;
    }
}
