<?php

/**
 * Plugin Name: Codeception library implementation plugin
 * Description: Write tests for non-plugin/non-theme libaries (ie. customizations loaded in functions.php)
 * Version: 1.0.0
 * Author: Aivec LLC.
 * Author URI: https://www.aivec.co.jp
 * License: GPL2
 *
 * @package Aivec
 */

// Put your implementation code here

require_once(ABSPATH . '/wp-content/plugins/wordpress-router/vendor/autoload.php');
require_once(__DIR__ . '/src/Rest.php');

add_action('init', function () {
    $router = new Aivec\Testing\Rest('/wp-router', 'wprouter_nonce_key', 'wprouter_nonce_name');
    $router->dispatcher->listen();
});

(new Aivec\WordPress\Routing\Admin\SettingsPage('test', 'Test Plugin'))->createSettingsPage();
