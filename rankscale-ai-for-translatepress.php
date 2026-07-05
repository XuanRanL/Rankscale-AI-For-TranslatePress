<?php
/**
 * Plugin Name: Rankscale AI For TranslatePress
 * Plugin URI: https://rankscaleai.com
 * Description: Supercharge TranslatePress with AI-powered translation using DeepSeek, Google Gemini, and OpenAI APIs. Features optimized prompts and automatic retry.
 * Version: 2.0.0
 * Author: Xuanran
 * Author URI: https://rankscaleai.com
 * Text Domain: rankscale-ai-for-translatepress
 * Domain Path: /languages
 * Requires PHP: 7.2
 * Requires at least: 6.0
 * Tested up to: 6.8
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


use Rankscale\TranslatePress\AI\Base\Activate;
use Rankscale\TranslatePress\AI\Base\Deactivate;
use Rankscale\TranslatePress\AI\Init;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( PHP_VERSION_ID < 70200 ) { 
	// show warning message
	if ( is_admin() ) {
		add_action( 'admin_notices', function ()
		{
			// translators: %1$s is the minimum PHP version required, %2$s is the current PHP version.
			$text = __( 'Rankscale AI For TranslatePress needs PHP %1$s. Your current PHP version is %2$s. Please upgrade to PHP to %1$s or a newer version, otherwise the plugin will have no effect.',
			'rankscale-ai-for-translatepress' );
			$formatted_text = sprintf( $text, '7.2.0', PHP_VERSION );
			printf( '<div class="error"><p>%s</p></div>', esc_html( $formatted_text ) );
		} );
	}

	return;
}


$trp_plugin = 'translatepress-multilingual/index.php';
$trp_active = in_array( $trp_plugin, (array) get_option( 'active_plugins', [] ) );
if ( ! $trp_active && is_multisite() ) {
    $trp_active = isset( get_site_option( 'active_sitewide_plugins', [] )[ $trp_plugin ] );
}
if ( ! $trp_active ) {
    if ( is_admin() ) {
        add_action( 'admin_notices', function () {
            printf( '<div class="error"><p>%s</p></div>',
                esc_html__( 'Rankscale AI For TranslatePress requires the TranslatePress - Multilingual plugin to be installed and activated.', 'rankscale-ai-for-translatepress' )
            );
        } );
    }
    return;
}

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
} else {
    if (is_admin()) {
        add_action('admin_notices', function () {
            printf(
                '<div class="error"><p>%s</p></div>',
                esc_html__('Rankscale AI For TranslatePress: vendor/autoload.php not found. Please run "composer dump-autoload" in the plugin directory.', 'rankscale-ai-for-translatepress')
            );
        });
    }
    return;
}

if (class_exists(Init::class)) {
    add_action('plugins_loaded', function () {
        load_plugin_textdomain('rankscale-ai-for-translatepress', false, dirname(plugin_basename(__FILE__)) . '/languages');
        Init::registerService();
    }, 1);
}

register_activation_hook(__FILE__, function () {
    Activate::handler();
});

register_deactivation_hook(__FILE__, function () {
    Deactivate::handler();
});