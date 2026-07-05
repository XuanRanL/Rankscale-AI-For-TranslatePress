<?php

namespace Rankscale\TranslatePress\AI\Base;

class Common
{
    const PLUGIN_VERSION = '2.0.0';

    const PLUGIN_ID = 'rankscale-ai-for-translatepress';

    public $plugin_path;

    public $plugin_url;

    public $plugin;

    public function __construct() {
        $this->plugin_path = plugin_dir_path( dirname( __FILE__, 3 ) . '/rankscale-ai-for-translatepress.php' );
        $this->plugin_url = plugin_dir_url( dirname( __FILE__, 3 ) . '/rankscale-ai-for-translatepress.php' );
        $this->plugin = plugin_basename( dirname( __FILE__, 3 ) ) . '/rankscale-ai-for-translatepress.php';
    }
}