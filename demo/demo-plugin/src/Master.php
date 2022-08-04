<?php

namespace Aivec\WordPressRouterDemo;

/**
 * Entrypoint class for routing demo plugin
 */
class Master
{
    /**
     * The `Routes` object
     *
     * @var Routes
     */
    public $routes;

    /**
     * Registers hooks
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function init() {
        add_action('init', [$this, 'initializeRouter']);
        add_action('admin_enqueue_scripts', [$this, 'loadScripts']);
    }

    /**
     * Instantiates the `Routes` class and listens for requests
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function initializeRouter() {
        $this->routes = new Routes('/demo', 'demo_nonce_key', 'demo_nonce_name');
        $this->routes->dispatcher->listen();
    }

    /**
     * Loads the script and injects route variables
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function loadScripts() {
        $scripts = [
            [
                'slug' => 'jquery-only',
                'path' => '/src/js/jquery-only.js',
            ],
            [
                'slug' => 'axios-only',
                'path' => '/dist/axios-only.js',
            ],
            [
                'slug' => 'axios-w-helper',
                'path' => '/dist/axios-w-helper.js',
            ],
        ];

        $myvars = $this->routes->getScriptInjectionVariables();

        foreach ($scripts as $script) {
            wp_enqueue_script($script['slug'], DEMO_PLUGIN_URL . $script['path'], [], '1.0.0', false);
            wp_localize_script($script['slug'], 'myvars', $myvars);
        }
    }
}
