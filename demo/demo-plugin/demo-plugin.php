<?php

/**
 * Plugin Name: aivec/wordpress-router demo plugin
 * Version: 1.0.0
 * Author: Aivec LLC.
 * Author URI: https://www.aivec.co.jp
 * License: GPL2
 *
 * @package Aivec
 */

define('DEMO_PLUGIN_URL', site_url() . '/wp-content/plugins/' . plugin_basename(__DIR__));

require_once(__DIR__ . '/vendor/autoload.php');

(new Aivec\WordPressRouterDemo\Master())->init();
