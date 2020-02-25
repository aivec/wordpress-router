<?php
namespace Aivec\WordPress\Routing;

use AWR\FastRoute as FastRoute;

/**
 * Create passthru routes (non-AJAX routes that do not invoke `die()`)
 */
class PassthruRouter extends Router {

    /**
     * Delegates requests to the appropriate class handler method
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\RouteCollector $r
     * @param string|string[]          $method GET, POST, PUT, etc.
     * @param string                   $route
     * @param callable                 $callable class method or function
     * @param callable[]               $middlewares array of middleware callables to be invoked
     *                                              before the route callable
     * @param callable[]               $aftermiddlewares array of callables to be invoked after
     *                                                   the route callable returns
     * @param boolean                  $noncecheck
     * @return void
     */
    public function add(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = [],
        $noncecheck = false
    ) {
        $r->addRoute($method, $route, function ($args) use ($middlewares, $aftermiddlewares, $callable, $noncecheck) {
            if ($noncecheck === true) {
                $nonce = '';
                if (isset($_REQUEST[$this->getNonceKey()])) {
                    $nonce = sanitize_text_field(wp_unslash($_REQUEST[$this->getNonceKey()]));
                }
                if (!wp_verify_nonce($nonce, $this->getNonceName())) {
                    die('Security check');
                }
            }
            $payload = $this->getJsonPayload();
            foreach ($middlewares as $middleware) {
                call_user_func($middleware, $args, $payload);
            }
            $res = call_user_func($callable, $args, $payload);
            foreach ($aftermiddlewares as $afterm) {
                $res = call_user_func($afterm, $res, $args, $payload);
            }
        });
    }
}
