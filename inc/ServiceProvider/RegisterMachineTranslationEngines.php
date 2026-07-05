<?php
namespace Rankscale\TranslatePress\AI\ServiceProvider;

use Rankscale\TranslatePress\AI\TranslationEngines\DeepSeekTranslationEngine;

class RegisterMachineTranslationEngines extends AbstractRegisterEngine
{
    protected function getEngineKey(): string   { return DeepSeekTranslationEngine::ENGINE_KEY; }
    protected function getEngineClass(): string { return DeepSeekTranslationEngine::class; }
    protected function getEngineLabel(): string { return 'DeepSeek'; }
    protected function getFilterPrefix(): string { return 'deepseek'; }
    protected function getApiKeyField(): string { return DeepSeekTranslationEngine::FIELD_API_KEY; }

    protected function renderEngineSettings($settings, $machine_translator, $show_errors, $error_message, $text_input_classes): void
    {
        ?>
        <span class="trp-primary-text-bold"><?php esc_html_e('DeepSeek API Key', 'rankscale-ai-for-translatepress'); ?></span>

        <div class="trp-automatic-translation-api-key-container">
            <input type="password" id="trp-deepseek-api-key"
                   class="<?php echo esc_attr(implode(' ', $text_input_classes)); ?>"
                   name="trp_machine_translation_settings[<?php echo esc_attr(DeepSeekTranslationEngine::FIELD_API_KEY); ?>]"
                   value="<?php if (!empty($settings[DeepSeekTranslationEngine::FIELD_API_KEY])) echo esc_attr($settings[DeepSeekTranslationEngine::FIELD_API_KEY]); ?>"/>
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
            $text = __('Visit <a href="%s" target="_blank">this link</a> to see how you can set up an API key and control API costs.', 'rankscale-ai-for-translatepress');
            echo wp_kses(sprintf($text, 'https://api-docs.deepseek.com/'), ['a' => ['href' => [], 'target' => []]]);
            ?>
        </span>

        <div style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('API Endpoint', 'rankscale-ai-for-translatepress'); ?></span>
            <select id="trp-deepseek-api-endpoint"
                    name="trp_machine_translation_settings[<?php echo esc_attr(DeepSeekTranslationEngine::FIELD_API_ENDPOINT); ?>]"
                    class="trp-select" style="margin-top: 5px;">
                <?php
                $selected_endpoint = $settings[DeepSeekTranslationEngine::FIELD_API_ENDPOINT] ?? 'siliconflow';
                foreach (DeepSeekTranslationEngine::AVAILABLE_ENDPOINTS as $ep_key => $ep_label) { ?>
                    <option value="<?php echo esc_attr($ep_key); ?>" <?php selected($selected_endpoint, $ep_key); ?>>
                        <?php echo esc_html($ep_label); ?>
                    </option>
                <?php } ?>
            </select>
            <span class="trp-description-text"><?php esc_html_e('Select the API endpoint for DeepSeek translations.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>

        <div id="trp-deepseek-custom-url-wrapper" style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('Custom API URL', 'rankscale-ai-for-translatepress'); ?></span>
            <input type="text" id="trp-deepseek-custom-api-url" class="trp-text-input" style="margin-top: 5px;"
                   name="trp_machine_translation_settings[<?php echo esc_attr(DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL); ?>]"
                   value="<?php echo esc_attr($settings[DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL] ?? ''); ?>"
                   placeholder="https://your-api-url.com/v1/chat/completions"/>
            <span class="trp-description-text"><?php esc_html_e('Enter a custom API URL when "Custom URL" is selected above. Must be OpenAI-compatible.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>

        <div style="margin-top: 15px;">
            <label>
                <input type="checkbox" id="trp-deepseek-enable-thinking"
                       name="trp_machine_translation_settings[<?php echo esc_attr(DeepSeekTranslationEngine::FIELD_ENABLE_THINKING); ?>]"
                       value="yes" <?php checked($settings[DeepSeekTranslationEngine::FIELD_ENABLE_THINKING] ?? 'no', 'yes'); ?> />
                <span class="trp-primary-text-bold"><?php esc_html_e('Enable Deep Thinking', 'rankscale-ai-for-translatepress'); ?></span>
            </label>
            <span class="trp-description-text" style="display: block; margin-top: 4px;">
                <?php esc_html_e('When enabled, the model will reason before translating, which may improve quality but increases latency and token usage.', 'rankscale-ai-for-translatepress'); ?>
            </span>
        </div>

        <div id="trp-deepseek-thinking-budget-wrapper" style="margin-top: 15px;">
            <span class="trp-primary-text-bold"><?php esc_html_e('Thinking Budget', 'rankscale-ai-for-translatepress'); ?></span>
            <input type="number" id="trp-deepseek-thinking-budget" class="trp-text-input" style="margin-top: 5px; width: 120px;"
                   name="trp_machine_translation_settings[<?php echo esc_attr(DeepSeekTranslationEngine::FIELD_THINKING_BUDGET); ?>]"
                   value="<?php echo esc_attr($settings[DeepSeekTranslationEngine::FIELD_THINKING_BUDGET] ?? 4096); ?>"
                   min="128" max="32768" step="128"/>
            <span class="trp-description-text"><?php esc_html_e('Maximum tokens for the thinking chain (128 - 32768, default: 4096). Higher values allow deeper reasoning but cost more tokens.', 'rankscale-ai-for-translatepress'); ?></span>
        </div>
        <?php
    }

    protected function sanitizeEngineSettings($settings, $mt_settings): array
    {
        if (!empty($mt_settings[DeepSeekTranslationEngine::FIELD_API_ENDPOINT]))
            $settings[DeepSeekTranslationEngine::FIELD_API_ENDPOINT] = sanitize_text_field($mt_settings[DeepSeekTranslationEngine::FIELD_API_ENDPOINT]);

        if (isset($mt_settings[DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL]))
            $settings[DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL] = esc_url_raw($mt_settings[DeepSeekTranslationEngine::FIELD_CUSTOM_API_URL]);

        $settings[DeepSeekTranslationEngine::FIELD_ENABLE_THINKING] = !empty($mt_settings[DeepSeekTranslationEngine::FIELD_ENABLE_THINKING]) ? 'yes' : 'no';

        if (isset($mt_settings[DeepSeekTranslationEngine::FIELD_THINKING_BUDGET])) {
            $budget = (int) $mt_settings[DeepSeekTranslationEngine::FIELD_THINKING_BUDGET];
            $settings[DeepSeekTranslationEngine::FIELD_THINKING_BUDGET] = max(128, min(32768, $budget));
        }

        return $settings;
    }
}
