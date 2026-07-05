<?php

namespace Rankscale\TranslatePress\AI\Base;

class Deactivate
{
    public static function handler()
    {
        self::cleanupLegacyTransients();
    }

    private static function cleanupLegacyTransients()
    {
        global $wpdb;
        $like_val     = $wpdb->esc_like('_transient_trp_ai_') . '%';
        $like_timeout = $wpdb->esc_like('_transient_timeout_trp_ai_') . '%';

        $batch = 1000;
        do {
            $deleted = (int) $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s LIMIT %d",
                    $like_val, $like_timeout, $batch
                )
            );
        } while ($deleted >= $batch);
    }
}
