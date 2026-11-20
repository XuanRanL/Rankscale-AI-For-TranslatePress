<?php
/**
 * Minimal stand-ins for WordPress and TranslatePress, so the plugin's own logic can be
 * tested with plain PHP:  php tests/run.php
 *
 * Nothing here talks to a network: wp_remote_post() answers from a queue the test fills.
 */

namespace {
    // Command line only: on a web server this file must do nothing.
    if (PHP_SAPI !== 'cli') {
        exit;
    }

    $GLOBALS['test_filters']    = [];   // filter name => callable
    $GLOBALS['test_http_queue'] = [];   // responses wp_remote_post() hands out, in order
    $GLOBALS['test_http_log']   = [];   // every request that was "sent"
    $GLOBALS['test_error_log']  = [];
    $GLOBALS['test_sleep']      = 0;

    class WP_Error
    {
        private $code;
        private $message;

        public function __construct($code = '', $message = '')
        {
            $this->code    = $code;
            $this->message = $message;
        }

        public function get_error_code()    { return $this->code; }
        public function get_error_message() { return $this->message; }
    }

    function is_wp_error($thing) { return $thing instanceof WP_Error; }

    function apply_filters($tag, $value, ...$args)
    {
        if (isset($GLOBALS['test_filters'][$tag])) {
            return call_user_func($GLOBALS['test_filters'][$tag], $value, ...$args);
        }
        return $value;
    }

    function __($text, $domain = 'default') { return $text; }

    // WordPress ships its own mb_substr() for PHP builds without mbstring (wp-includes/compat.php).
    if (!function_exists('mb_substr')) {
        function mb_substr($string, $start, $length = null, $encoding = null)
        {
            return ($length === null) ? substr($string, $start) : substr($string, $start, $length);
        }
    }

    function wp_json_encode($data, $options = 0, $depth = 512) { return json_encode($data, $options, $depth); }

    // Transients, kept in memory. The lifetime is recorded so a test can read it; nothing expires here.
    $GLOBALS['test_transients'] = [];

    function get_transient($name)
    {
        return isset($GLOBALS['test_transients'][$name]) ? $GLOBALS['test_transients'][$name]['value'] : false;
    }

    function set_transient($name, $value, $ttl = 0)
    {
        $GLOBALS['test_transients'][$name] = ['value' => $value, 'ttl' => (int) $ttl];
        return true;
    }

    function delete_transient($name)
    {
        unset($GLOBALS['test_transients'][$name]);
        return true;
    }

    function sanitize_text_field($text) { return trim(preg_replace('/[\r\n\t ]+/', ' ', strip_tags((string) $text))); }

    function esc_url_raw($url) { return preg_match('#^https?://#i', (string) $url) ? trim((string) $url) : ''; }

    function wp_remote_post($url, $args = [])
    {
        $GLOBALS['test_http_log'][] = ['url' => $url, 'args' => $args, 'body' => json_decode($args['body'] ?? '', true)];
        if (empty($GLOBALS['test_http_queue'])) {
            return new WP_Error('http_request_failed', 'test: no response queued');
        }
        return array_shift($GLOBALS['test_http_queue']);
    }

    function wp_remote_retrieve_response_code($response)
    {
        return (is_array($response) && isset($response['response']['code'])) ? $response['response']['code'] : '';
    }

    function wp_remote_retrieve_header($response, $header)
    {
        return (is_array($response) && isset($response['headers'][$header])) ? $response['headers'][$header] : '';
    }

    class Test_Machine_Translator_Logger
    {
        public $counted = 0;
        public $logged  = 0;

        public function log($args = [])                 { $this->logged++; return true; }
        public function count_towards_quota($strings)   { $this->counted += count($strings); return true; }
        public function quota_exceeded()                { return false; }
    }

    /** Stand-in for TranslatePress's engine base class: only what the engines touch. */
    class TRP_Machine_Translator
    {
        public $settings;
        public $machine_translator_logger;
        public $machine_translation_codes = ['en_US' => 'en', 'de_DE' => 'de', 'ko_KR' => 'ko'];

        public function __construct($settings)
        {
            $this->settings                  = $settings;
            $this->machine_translator_logger = new Test_Machine_Translator_Logger();
        }

        public function verify_request_parameters($to_language, $from_language) { return true; }
        public function get_referer()                                           { return 'https://example.test'; }
        public function get_chunk_size()                                        { return 50; }
    }
}

// Unqualified function calls inside a namespace look there first: these keep the tests
// from sleeping and collect the log lines instead of writing them.
namespace Rankscale\TranslatePress\AI\Helpers {
    function sleep($seconds)   { $GLOBALS['test_sleep'] += (int) $seconds; return 0; }
    function error_log($message) { $GLOBALS['test_error_log'][] = (string) $message; return true; }
}

namespace Rankscale\TranslatePress\AI\TranslationEngines {
    function error_log($message) { $GLOBALS['test_error_log'][] = (string) $message; return true; }
}

namespace {
    require dirname(__DIR__) . '/vendor/autoload.php';
}
