<?php
namespace Aivec\WordPress\Routing;

use AWR\FastRoute\RouteCollector;

/**
 * Route collector for WordPress REST routes
 */
class WordPressRouteCollector extends RouteCollector {

    /**
     * WordPress nonce key for POST/AJAX requests
     *
     * @var string
     */
    protected $nonce_key = '';

    /**
     * WordPress nonce name for POST/AJAX requests
     *
     * @var string
     */
    protected $nonce_name = '';

    /**
     * Sets `nonce_key`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $nonce_key
     * @return void
     */
    public function setNonceKey($nonce_key) {
        $this->nonce_key = $nonce_key;
    }

    /**
     * Sets `nonce_name`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $nonce_name
     * @return void
     */
    public function setNonceName($nonce_name) {
        $this->nonce_name = $nonce_name;
    }

    /**
     * Delegates requests to the appropriate class handler method
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares array of middleware callables to be invoked
     *                                     before the route callable
     * @param callable[]      $aftermiddlewares array of callables to be invoked after
     *                                          the route callable returns
     * @param boolean         $noncecheck
     * @param string          $role role to check for the current user
     * @return void
     */
    public function addWordPressRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = [],
        $noncecheck = false,
        $role = ''
    ) {
        $this->addRoute($method, $route, function ($args) use (
            $middlewares,
            $aftermiddlewares,
            $callable,
            $noncecheck,
            $role
        ) {
            if ($noncecheck === true) {
                check_ajax_referer($this->nonce_name, $this->nonce_key);
            }
            if (!empty($role)) {
                $user = wp_get_current_user();
                if (!in_array(strtolower($role), (array)$user->roles, true)) {
                    http_response_code(403);
                    die(-1);
                }
            }
            $payload = $this->getJsonPayload();
            foreach ($middlewares as $middleware) {
                $res = call_user_func($middleware, $args, $payload);
                if (!empty($res)) {
                    die($res);
                }
            }
            $res = call_user_func($callable, $args, $payload);
            foreach ($aftermiddlewares as $afterm) {
                $res = call_user_func($afterm, $res, $args, $payload);
            }
            if (empty($res)) {
                die(0);
            }
            die($res);
        });
    }

    /**
     * Adds a nonce verified administrator route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function addAdministratorRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, 'administrator');
    }

    /**
     * Adds a nonce verified editor route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function addEditorRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, 'editor');
    }

    /**
     * Adds a nonce verified author route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function addAuthorRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, 'author');
    }

    /**
     * Adds a nonce verified contributor route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function addContributorRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, 'contributor');
    }

    /**
     * Adds a nonce verified subscriber route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function addSubscriberRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, 'subscriber');
    }

    /**
     * Adds a public (no nonce check) route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function addPublicRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, false);
    }

    /**
     * Adds a nonce verified route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method GET, POST, PUT, etc.
     * @param string          $route
     * @param callable        $callable class method or function
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     */
    public function add(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true);
    }

    /**
     * Adds a GET route to the collection
     *
     * This is simply an alias of $this->add('GET', $route, $handler)
     *
     * @param string     $route
     * @param mixed      $handler
     * @param callable[] $middlewares
     * @param callable[] $aftermiddlewares
     */
    public function get($route, $handler, array $middlewares = [], array $aftermiddlewares = []) {
        $this->add('GET', $route, $handler, $middlewares, $aftermiddlewares);
    }

    /**
     * Adds a POST route to the collection
     *
     * This is simply an alias of $this->add('POST', $route, $handler)
     *
     * @param string     $route
     * @param mixed      $handler
     * @param callable[] $middlewares
     * @param callable[] $aftermiddlewares
     */
    public function post($route, $handler, array $middlewares = [], array $aftermiddlewares = []) {
        $this->add('POST', $route, $handler, $middlewares, $aftermiddlewares);
    }

    /**
     * Adds a PUT route to the collection
     *
     * This is simply an alias of $this->add('PUT', $route, $handler)
     *
     * @param string     $route
     * @param mixed      $handler
     * @param callable[] $middlewares
     * @param callable[] $aftermiddlewares
     */
    public function put($route, $handler, array $middlewares = [], array $aftermiddlewares = []) {
        $this->add('PUT', $route, $handler, $middlewares, $aftermiddlewares);
    }

    /**
     * Adds a DELETE route to the collection
     *
     * This is simply an alias of $this->add('DELETE', $route, $handler)
     *
     * @param string     $route
     * @param mixed      $handler
     * @param callable[] $middlewares
     * @param callable[] $aftermiddlewares
     */
    public function delete($route, $handler, array $middlewares = [], array $aftermiddlewares = []) {
        $this->add('DELETE', $route, $handler, $middlewares, $aftermiddlewares);
    }

    /**
     * Adds a PATCH route to the collection
     *
     * This is simply an alias of $this->add('PATCH', $route, $handler)
     *
     * @param string     $route
     * @param mixed      $handler
     * @param callable[] $middlewares
     * @param callable[] $aftermiddlewares
     */
    public function patch($route, $handler, array $middlewares = [], array $aftermiddlewares = []) {
        $this->add('PATCH', $route, $handler, $middlewares, $aftermiddlewares);
    }

    /**
     * Adds a HEAD route to the collection
     *
     * This is simply an alias of $this->add('HEAD', $route, $handler)
     *
     * @param string     $route
     * @param mixed      $handler
     * @param callable[] $middlewares
     * @param callable[] $aftermiddlewares
     */
    public function head($route, $handler, array $middlewares = [], array $aftermiddlewares = []) {
        $this->add('HEAD', $route, $handler, $middlewares, $aftermiddlewares);
    }

    /**
     * Decodes JSON body if set. Returns null if not set
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getJsonPayload() {
        $body = isset($_REQUEST['payload']) ? $_REQUEST['payload'] : null;
        if (empty($body)) {
            $body = null;
        }
        $payload = json_decode($body, true);
        if ($payload === null) {
            $payload = json_decode(wp_unslash($body), true);
        }

        return !empty($payload) ? $payload : [];
    }
}
