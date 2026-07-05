<?php
namespace Rankscale\TranslatePress\AI\ServiceProvider;

use Rankscale\TranslatePress\AI\Base\ServiceProviderInterface;
use TRP_Translate_Press;

abstract class AbstractRegisterEngine implements ServiceProviderInterface
{
    /** @return string  ENGINE_KEY constant value, e.g. 'deepseek_translate' */
    abstract protected function getEngineKey(): string;

    /** @return string  Fully-qualified engine class name */
    abstract protected function getEngineClass(): string;

    /** @return string  Display label, e.g. 'DeepSeek' */
    abstract protected function getEngineLabel(): string;

    /** @return string  Filter prefix, e.g. 'deepseek' */
    abstract protected function getFilterPrefix(): string;

    /** @return string  FIELD_API_KEY constant value */
    abstract protected function getApiKeyField(): string;

    /**
     * Render engine-specific settings rows (API key, model, endpoint, etc.).
     */
    abstract protected function renderEngineSettings($settings, $machine_translator, $show_errors, $error_message, $text_input_classes): void;

    /**
     * Sanitize engine-specific settings (called after the common API key sanitize).
     * @return array
     */
    abstract protected function sanitizeEngineSettings($settings, $mt_settings): array;

    public function register()
    {
        add_filter('trp_machine_translation_engines', [$this, 'add_engine'], 20);
        add_filter('trp_automatic_translation_engines_classes', [$this, 'add_engine_classes'], 20, 1);
        add_action('trp_machine_translation_extra_settings_middle', [$this, 'add_settings'], 20, 1);
        add_filter('trp_machine_translation_sanitize_settings', [$this, 'sanitize_settings'], 20, 2);

        $prefix = $this->getFilterPrefix();
        add_filter("trp_{$prefix}_target_language", [$this, 'configure_api_target_language'], 20, 3);
        add_filter("trp_{$prefix}_source_language", [$this, 'configure_api_source_language'], 20, 3);
    }

    public function add_engine_classes($classes)
    {
        $classes[$this->getEngineKey()] = $this->getEngineClass();
        return $classes;
    }

    public function add_engine($engines)
    {
        $engines[] = [
            'value' => $this->getEngineKey(),
            'label' => $this->getEngineLabel(),
        ];
        return $engines;
    }

    public function add_settings($settings)
    {
        $translation_engine = $settings['translation-engine'] ?? '';
        $is_active_engine   = ($this->getEngineKey() === $translation_engine);

        $show_errors   = false;
        $error_message = '';
        $machine_translator = null;

        if ($is_active_engine) {
            $trp                = TRP_Translate_Press::get_trp_instance();
            $machine_translator = $trp->get_component('machine_translator');

            $api_check = $machine_translator->check_api_key_validity();
            if (isset($api_check) && true === $api_check['error']) {
                $error_message = $api_check['message'];
                $show_errors   = true;
            }
        }

        $text_input_classes = ['trp-text-input'];
        if ($show_errors) {
            $text_input_classes[] = 'trp-text-input-error';
        }

        ?>
        <div class="trp-engine trp-automatic-translation-engine__container" id="<?php echo esc_attr($this->getEngineKey()); ?>">
        <?php
        $this->renderEngineSettings($settings, $machine_translator, $show_errors, $error_message, $text_input_classes);
        ?>
        </div>
        <?php
    }

    public function sanitize_settings($settings, $mt_settings)
    {
        $apiKeyField = $this->getApiKeyField();
        if (isset($mt_settings[$apiKeyField])) {
            $settings[$apiKeyField] = sanitize_text_field($mt_settings[$apiKeyField]);
        }

        return $this->sanitizeEngineSettings($settings, $mt_settings);
    }

    protected function getLanguageExceptionsMappingCodes(): array
    {
        return [
            'zh_HK'       => 'zh-tw',
            'zh_TW'       => 'zh-tw',
            'zh_CN'       => 'zh-cn',
            'en_GB'       => 'en',
            'en_US'       => 'en',
            'en_CA'       => 'en',
            'en_ZA'       => 'en',
            'en_NZ'       => 'en',
            'en_AU'       => 'en',
            'pt_BR'       => 'pt',
            'pt_PT'       => 'pt',
            'pt_AO'       => 'pt',
            'fr_FR'       => 'fr',
            'fr_CA'       => 'fr',
            'fr_BE'       => 'fr',
            'fr_CH'       => 'fr',
            'de_DE'       => 'de',
            'de_AT'       => 'de',
            'de_CH'       => 'de',
            'de_DE_formal' => 'de',
            'de_CH_informal' => 'de',
            'es_MX'       => 'es',
            'es_AR'       => 'es',
            'es_CO'       => 'es',
            'es_CL'       => 'es',
            'es_PE'       => 'es',
            'es_VE'       => 'es',
            'es_GT'       => 'es',
            'es_EC'       => 'es',
            'es_CR'       => 'es',
            'es_UY'       => 'es',
            'it_IT'       => 'it',
            'nl_NL'       => 'nl',
            'nl_BE'       => 'nl',
            'nl_NL_formal' => 'nl',
            'ru_RU'       => 'ru',
            'pl_PL'       => 'pl',
            'ro_RO'       => 'ro',
            'hu_HU'       => 'hu',
            'cs_CZ'       => 'cs',
            'sk_SK'       => 'sk',
            'bg_BG'       => 'bg',
            'hr'          => 'hr',
            'sr_RS'       => 'sr',
            'sl_SI'       => 'sl',
            'uk'          => 'uk',
            'el'          => 'el',
            'da_DK'       => 'da',
            'fi'          => 'fi',
            'sv_SE'       => 'sv',
            'nb_NO'       => 'nb',
            'nn_NO'       => 'nn',
            'et'          => 'et',
            'lt_LT'       => 'lt',
            'lv'          => 'lv',
            'tr_TR'       => 'tr',
            'ar'          => 'ar',
            'he_IL'       => 'he',
            'hi_IN'       => 'hi',
            'ja'          => 'ja',
            'ko_KR'       => 'ko',
            'th'          => 'th',
            'vi'          => 'vi',
            'id_ID'       => 'id',
            'ms_MY'       => 'ms',
            'tl'          => 'tl',
            'bn_BD'       => 'bn',
            'ta_IN'       => 'ta',
            'te'          => 'te',
            'mr'          => 'mr',
            'gu'          => 'gu',
            'kn'          => 'kn',
            'ml_IN'       => 'ml',
            'pa_IN'       => 'pa',
            'ur'          => 'ur',
            'fa_IR'       => 'fa',
            'sw'          => 'sw',
            'af'          => 'af',
            'sq'          => 'sq',
            'am'          => 'am',
            'hy'          => 'hy',
            'az'          => 'az',
            'eu'          => 'eu',
            'be'          => 'be',
            'bs_BA'       => 'bs',
            'ca'          => 'ca',
            'ka_GE'       => 'ka',
            'is_IS'       => 'is',
            'km'          => 'km',
            'mk_MK'       => 'mk',
            'mn'          => 'mn',
            'my_MM'       => 'my',
            'ne_NP'       => 'ne',
            'si_LK'       => 'si',
            'cy'          => 'cy',
            'gl_ES'       => 'gl',
            'mt_MT'       => 'mt',
            'lo'          => 'lo',
            'uz_UZ'       => 'uz',
            'kk'          => 'kk',
            'ky_KY'       => 'ky',
            'tg'          => 'tg',
            'ps'          => 'ps',
        ];
    }

    public function configure_api_source_language($source_language, $source_language_code, $target_language_code)
    {
        $mapping = $this->getLanguageExceptionsMappingCodes();
        if (isset($mapping[$source_language_code])) {
            $source_language = $mapping[$source_language_code];
        }
        return $source_language;
    }

    public function configure_api_target_language($target_language, $source_language_code, $target_language_code)
    {
        $mapping = $this->getLanguageExceptionsMappingCodes();
        if (isset($mapping[$target_language_code])) {
            $target_language = $mapping[$target_language_code];
        }
        return $target_language;
    }
}
