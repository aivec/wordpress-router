<?php

namespace Aivec\WordPressRouterDemo;

use DEMO\Aivec\WordPress\Routing\Router;
use DEMO\Aivec\WordPress\Routing\WordPressRouteCollector;

/**
 * Extends `Router` so we can define our routes
 */
class Routes extends Router
{
    /**
     * Contains declarations for all routes
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param WordPressRouteCollector $r
     * @return void
     */
    public function declareRoutes(WordPressRouteCollector $r) {
        $r->add('POST', '/hamburger', function () {
            return 'Here is a private hamburger.';
        });
    }
}
