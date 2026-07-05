<?php

namespace Rankscale\TranslatePress\AI\Base;

class Activate
{
    public static function handler()
    {
        if (!class_exists('TRP_Translate_Press')) {
            deactivate_plugins(plugin_basename(dirname(__FILE__, 3) . '/rankscale-ai-for-translatepress.php'));
            wp_die(
                esc_html__('Rankscale AI For TranslatePress requires the TranslatePress - Multilingual plugin to be installed and activated.', 'rankscale-ai-for-translatepress'),
                'Plugin dependency check',
                ['back_link' => true]
            );
        }
    }
}
