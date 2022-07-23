<?php

namespace Aivec\Testing;

use Aivec\WordPress\Routing\Middleware\JWT;
use Aivec\WordPress\Routing\Router;
use Aivec\WordPress\Routing\WordPressRouteCollector;

/**
 * Declares all REST routes
 */
class Rest extends Router
{
    /**
     * Declares routes
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param WordPressRouteCollector $r
     * @return void
     */
    public function declareRoutes(WordPressRouteCollector $r): void {
        $r->addGroup('/test', function (WordPressRouteCollector $r) {
            $jwt = function (array $args, $payload) {
                return $payload;
            };

            // Public REST routes
            $r->addPublicJwtRoute('POST', '/jwt', $jwt, new JWT(ABSPATH . '/pub-key.pem'));
        });
    }
}
