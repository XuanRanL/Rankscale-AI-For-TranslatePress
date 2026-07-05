<?php
namespace Rankscale\TranslatePress\AI\Helpers;

class RetryHelper
{
    const RETRYABLE_CODES = [429, 500, 502, 503, 504];

    /**
     * Execute $fn and retry on transient HTTP errors with exponential back-off.
     *
     * @param callable $fn         Must return array|WP_Error (wp_remote_post result)
     * @param int      $maxRetries Maximum number of retries (default 1 = 2 total attempts)
     * @return array|\WP_Error
     */
    public static function withRetry(callable $fn, $maxRetries = 1)
    {
        $lastResponse = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $response = $fn();

            if (is_wp_error($response)) {
                $msg = $response->get_error_message();
                if ($attempt < $maxRetries && self::isRetryableWpError($msg)) {
                    error_log(sprintf('[Rankscale AI] Retry %d/%d after WP_Error: %s', $attempt + 1, $maxRetries, $msg));
                    sleep(min(pow(2, $attempt) + 1, 5));
                    continue;
                }
                return $response;
            }

            $code = (int) wp_remote_retrieve_response_code($response);

            if ($code === 200 || !in_array($code, self::RETRYABLE_CODES, true)) {
                return $response;
            }

            $lastResponse = $response;

            if ($attempt < $maxRetries) {
                $retryAfter = wp_remote_retrieve_header($response, 'retry-after');
                $delay      = self::parseRetryAfter($retryAfter, $attempt);
                if (function_exists('set_time_limit')) {
                    @set_time_limit(0);
                }
                sleep($delay);
                error_log(sprintf(
                    '[Rankscale AI] Retry %d/%d after HTTP %d (delay %ds)',
                    $attempt + 1, $maxRetries, $code, $delay
                ));
            }
        }

        return $lastResponse;
    }

    private static function parseRetryAfter($header, $attempt)
    {
        $default = min(pow(2, $attempt) + 1, 5);
        if (empty($header)) {
            return $default;
        }
        if (is_numeric($header)) {
            return min((int) $header, 8);
        }
        $timestamp = strtotime($header);
        if ($timestamp !== false) {
            $wait = $timestamp - time();
            return ($wait > 0) ? min($wait, 8) : $default;
        }
        return $default;
    }

    private static function isRetryableWpError($message)
    {
        $retryable = ['timed out', 'timeout', 'reset by peer', 'connection refused', 'temporarily unavailable'];
        $lower     = strtolower($message);
        foreach ($retryable as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }
}
