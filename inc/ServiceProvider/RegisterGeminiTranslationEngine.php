<?php
namespace Rankscale\TranslatePress\AI\ServiceProvider;

use Rankscale\TranslatePress\AI\TranslationEngines\GeminiTranslationEngine;

class RegisterGeminiTranslationEngine extends AbstractRegisterEngine
{
    protected function getEngineKey(): string    { return GeminiTranslationEngine::ENGINE_KEY; }
    protected function getEngineClass(): string  { return GeminiTranslationEngine::class; }
    protected function getEngineLabel(): string  { return 'Google Gemini'; }
    protected function getFilterPrefix(): string { return 'gemini'; }
    protected function getApiKeyField(): string  { return GeminiTranslationEngine::FIELD_API_KEY; }

    protected function renderEngineSettings($settings, $machine_translator, $show_errors, $error_message, $text_input_classes): void
    {
        ?>
        <span class="trp-primary-text-bold"><?php esc_html_e('Google Gemini API Key', 'rankscale-ai-for-translatepress'); ?></span>

        <div class="trp-automatic-translation-api-key-container">
            <input type="password" id="trp-gemini-api-key"
                   class="<?php echo esc_attr(implode(' ', $text_input_classes)); ?>"
                   name="trp_machine_translation_settings[<?php echo esc_attr(GeminiTranslationEngine::FIELD_API_KEY); ?>]"
                   value="<?php if (!empty($settings[GeminiTranslationEngine::FIELD_API_KEY])) echo esc_attr($settings[GeminiTranslationEngine::FIELD_API_KEY]); ?>"/>
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
            $text = __('Get your API key from <a href="%s" target="_blank">Google AI Studio</a>. It\'s free for most use cases.', 'rankscale-ai-for-translatepress');
            echo wp_kses(sprintf($text, 'https://aistudio.google.com/app/apikey'), ['a' => ['href' => [], 'target' => []]]);
            ?>
        </span>

        <div style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('Gemini Model', 'rankscale-ai-for-translatepress'); ?></span>
            <select id="trp-gemini-model"
                    name="trp_machine_translation_settings[<?php echo esc_attr(GeminiTranslationEngine::FIELD_MODEL); ?>]"
                    class="trp-select" style="margin-top: 5px;">
                <?php
                $selected_model = $settings[GeminiTranslationEngine::FIELD_MODEL] ?? GeminiTranslationEngine::DEFAULT_MODEL;
                foreach (GeminiTranslationEngine::AVAILABLE_MODELS as $model_key => $model_label) { ?>
                    <option value="<?php echo esc_attr($model_key); ?>" <?php selected($selected_model, $model_key); ?>>
                        <?php echo esc_html($model_label); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="trp-description-text"><?php esc_html_e('Choose the Gemini model. Flash is faster and cheaper, Pro offers better quality.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>

        <div id="trp-gemini-custom-model-wrapper" style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('Custom Model ID', 'rankscale-ai-for-translatepress'); ?></span>
            <input type="text" id="trp-gemini-custom-model" class="trp-text-input" style="margin-top: 5px;"
                   name="trp_machine_translation_settings[<?php echo esc_attr(GeminiTranslationEngine::FIELD_CUSTOM_MODEL); ?>]"
                   value="<?php echo esc_attr($settings[GeminiTranslationEngine::FIELD_CUSTOM_MODEL] ?? ''); ?>"
                   placeholder="gemini-3.6-flash"/>
            <span class="trp-description-text"><?php esc_html_e('Enter any Gemini model ID exactly as listed in the Gemini API docs. Used when "Custom model ID…" is selected above, so new models work without a plugin update.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>
        <?php
    }

    protected function sanitizeEngineSettings($settings, $mt_settings): array
    {
        if (!empty($mt_settings[GeminiTranslationEngine::FIELD_MODEL]))
            $settings[GeminiTranslationEngine::FIELD_MODEL] = sanitize_text_field($mt_settings[GeminiTranslationEngine::FIELD_MODEL]);

        if (isset($mt_settings[GeminiTranslationEngine::FIELD_CUSTOM_MODEL]))
            $settings[GeminiTranslationEngine::FIELD_CUSTOM_MODEL] = sanitize_text_field(trim($mt_settings[GeminiTranslationEngine::FIELD_CUSTOM_MODEL]));

        return $settings;
    }
}
