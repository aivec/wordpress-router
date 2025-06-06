<?php

namespace Aivec\WordPress\Routing;

use FastRoute\RouteCollector;
use InvalidArgumentException;
use Exception;

/**
 * Route collector for WordPress REST routes
 */
class WordPressRouteCollector extends RouteCollector
{
    const ROUTE_KEY = 'awr_rest_route';

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
     * Handles route execution
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param array      $args
     * @param array      $payload
     * @param callable   $callable
     * @param array      $middlewares
     * @param array      $aftermiddlewares
     * @param bool       $noncecheck
     * @param array|null $roles
     * @return void
     */
    public function handleRoute(
        $args,
        $payload,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = [],
        $noncecheck = false,
        array $roles = null
    ) {
        $ecode = rest_authorization_required_code() === 403 ? Errors::FORBIDDEN : Errors::UNAUTHORIZED;
        if ($noncecheck === true) {
            $valid = check_ajax_referer($this->nonce_name, $this->nonce_key, false);
            if ($valid === false) {
                die(json_encode((new Errors())->getErrorResponse($ecode)));
            }
        }
        if (!empty($roles)) {
            $exists = false;
            $user = wp_get_current_user();
            foreach ($roles as $role) {
                if (in_array(strtolower($role), (array)$user->roles, true)) {
                    $exists = true;
                    break;
                }
            }

            if ($exists === false) {
                die(json_encode((new Errors())->getErrorResponse($ecode)));
            }
        }
        foreach ($middlewares as $middleware) {
            $res = call_user_func($middleware, $args, $payload);
            if (!empty($res)) {
                if (is_string($res)) {
                    json_decode($res);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // $res is already JSON
                        die($res);
                    }
                }
                die(json_encode($res));
            }
        }
        $res = call_user_func($callable, $args, $payload);
        foreach ($aftermiddlewares as $afterm) {
            $res = call_user_func($afterm, $res, $args, $payload);
        }
        if (is_string($res)) {
            json_decode($res);
            if (json_last_error() === JSON_ERROR_NONE) {
                // $res is already JSON
                die($res);
            }
        }
        die(json_encode($res));
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
     * @param string[]|null   $roles roles to check for the current user
     * @return void
     */
    public function addWordPressRoute(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = [],
        $noncecheck = false,
        array $roles = null
    ) {
        $this->addRoute($method, $route, function ($args) use (
            $middlewares,
            $aftermiddlewares,
            $callable,
            $noncecheck,
            $roles
        ) {
            $payload = $this->getJsonPayload();
            $this->handleRoute($args, $payload, $callable, $middlewares, $aftermiddlewares, $noncecheck, $roles);
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
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, ['administrator']);
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
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, ['editor']);
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
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, ['author']);
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
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, ['contributor']);
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
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, ['subscriber']);
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
     * Adds a public (no nonce check) route with JWT authentication middleware
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::addWordPressRoute()
     * @param string|string[] $method POST and/or PUT
     * @param string          $route
     * @param callable        $callable class method or function
     * @param Middleware\JWT  $jwt
     * @param callable[]      $middlewares
     * @param callable[]      $aftermiddlewares
     * @return void
     * @throws InvalidArgumentException Thrown when $method is wrong.
     */
    public function addPublicJwtRoute(
        $method,
        $route,
        callable $callable,
        Middleware\JWT $jwt,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        if (!is_array($method)) {
            $method = [$method];
        }
        foreach ($method as $m) {
            $m = strtoupper($m);
            if ($m !== 'POST' && $m !== 'PUT') {
                throw new InvalidArgumentException('Only "POST" and "PUT" are allowed for $method');
            }
        }
        $this->addRoute($method, $route, function ($args) use (
            $callable,
            $jwt,
            $middlewares,
            $aftermiddlewares
        ) {
            $payload = [];
            $rawjson = $this->getJsonPayload();
            try {
                $payload = $jwt->decode(!empty($rawjson['jwt']) ? (string)$rawjson['jwt'] : '');
            } catch (Exception $e) {
                $errors = new Errors();
                $errors->populate();
                die(json_encode($errors->getErrorResponse(Errors::JWT_UNAUTHORIZED, [$e->getMessage()])));
            }

            $this->handleRoute($args, $payload, $callable, $middlewares, $aftermiddlewares);
        });
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
     * @param string[]|null   $roles
     * @return void
     */
    public function add(
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = [],
        array $roles = null
    ) {
        $this->addWordPressRoute($method, $route, $callable, $middlewares, $aftermiddlewares, true, $roles);
    }

    /**
     * Adds a GET route to the collection
     *
     * This is simply an alias of $this->add('GET', $route, $handler)
     *
     * @param string        $route
     * @param mixed         $handler
     * @param callable[]    $middlewares
     * @param callable[]    $aftermiddlewares
     * @param string[]|null $roles
     * @return void
     */
    public function get($route, $handler, array $middlewares = [], array $aftermiddlewares = [], array $roles = null) {
        $this->add('GET', $route, $handler, $middlewares, $aftermiddlewares, $roles);
    }

    /**
     * Adds a POST route to the collection
     *
     * This is simply an alias of $this->add('POST', $route, $handler)
     *
     * @param string        $route
     * @param mixed         $handler
     * @param callable[]    $middlewares
     * @param callable[]    $aftermiddlewares
     * @param string[]|null $roles
     * @return void
     */
    public function post($route, $handler, array $middlewares = [], array $aftermiddlewares = [], array $roles = null) {
        $this->add('POST', $route, $handler, $middlewares, $aftermiddlewares, $roles);
    }

    /**
     * Adds a PUT route to the collection
     *
     * This is simply an alias of $this->add('PUT', $route, $handler)
     *
     * @param string        $route
     * @param mixed         $handler
     * @param callable[]    $middlewares
     * @param callable[]    $aftermiddlewares
     * @param string[]|null $roles
     * @return void
     */
    public function put($route, $handler, array $middlewares = [], array $aftermiddlewares = [], array $roles = null) {
        $this->add('PUT', $route, $handler, $middlewares, $aftermiddlewares, $roles);
    }

    /**
     * Adds a DELETE route to the collection
     *
     * This is simply an alias of $this->add('DELETE', $route, $handler)
     *
     * @param string        $route
     * @param mixed         $handler
     * @param callable[]    $middlewares
     * @param callable[]    $aftermiddlewares
     * @param string[]|null $roles
     * @return void
     */
    public function delete($route, $handler, array $middlewares = [], array $aftermiddlewares = [], array $roles = null) {
        $this->add('DELETE', $route, $handler, $middlewares, $aftermiddlewares, $roles);
    }

    /**
     * Adds a PATCH route to the collection
     *
     * This is simply an alias of $this->add('PATCH', $route, $handler)
     *
     * @param string        $route
     * @param mixed         $handler
     * @param callable[]    $middlewares
     * @param callable[]    $aftermiddlewares
     * @param string[]|null $roles
     * @return void
     */
    public function patch($route, $handler, array $middlewares = [], array $aftermiddlewares = [], array $roles = null) {
        $this->add('PATCH', $route, $handler, $middlewares, $aftermiddlewares, $roles);
    }

    /**
     * Adds a HEAD route to the collection
     *
     * This is simply an alias of $this->add('HEAD', $route, $handler)
     *
     * @param string        $route
     * @param mixed         $handler
     * @param callable[]    $middlewares
     * @param callable[]    $aftermiddlewares
     * @param string[]|null $roles
     * @return void
     */
    public function head($route, $handler, array $middlewares = [], array $aftermiddlewares = [], array $roles = null) {
        $this->add('HEAD', $route, $handler, $middlewares, $aftermiddlewares, $roles);
    }

    /**
     * Decodes JSON body if set. Returns empty array if not set
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getJsonPayload() {
        $body = isset($_REQUEST['payload']) ? $_REQUEST['payload'] : null;
        if (empty($body)) {
            return [];
        }
        $payload = json_decode($body, true);
        if ($payload === null) {
            $payload = json_decode(wp_unslash($body), true);
        }

        return !empty($payload) ? $payload : [];
    }
}
