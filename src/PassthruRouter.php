<?php
namespace Aivec\WordPress\Routing;

use AWR\FastRoute as FastRoute;

/**
 * Create passthru routes (non-AJAX routes that do not invoke `die()`)
 *
 * In cases where template routing is being delegated to WordPress, the
 * `RequestKeyRouter` should be used instead.
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
     * @param string                   $role role to check for the current user
     * @return void
     */
    public function add(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = [],
        $noncecheck = false,
        $role = ''
    ) {
        $r->addRoute($method, $route, function ($args) use (
            $middlewares,
            $aftermiddlewares,
            $callable,
            $noncecheck,
            $role
        ) {
            if ($noncecheck === true) {
                $nonce = '';
                if (isset($_REQUEST[$this->getNonceKey()])) {
                    $nonce = sanitize_text_field(wp_unslash($_REQUEST[$this->getNonceKey()]));
                }
                if (!wp_verify_nonce($nonce, $this->getNonceName())) {
                    die('Forbidden');
                }
            }
            if (!empty($role)) {
                $user = wp_get_current_user();
                if (!in_array(strtolower($role), (array)$user->roles, true)) {
                    http_response_code(403);
                    die('Forbidden');
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
