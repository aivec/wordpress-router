<?php
namespace Aivec\WordPress\Routing;

use InvalidArgumentException;
use RuntimeException;
use AWR\FastRoute as FastRoute;

/**
 * Request handler factory
 *
 * NOTE: This class MUST be instantiated AFTER WordPress core functions are loaded (ie. some
 * time after 'plugins_loaded', 'init', or any other appropriate WordPress hook)
 */
class Router {

    /**
     * Namespace (group) for all routes
     *
     * @var string
     */
    private $routes_namespace;

    /**
     * WordPress nonce key for POST/AJAX requests
     *
     * @var string
     */
    private $nonce_key;

    /**
     * WordPress nonce name for POST/AJAX requests
     *
     * @var string
     */
    private $nonce_name;

    /**
     * WordPress nonce token
     *
     * @var string
     */
    private $nonce;

    /**
     * WordPress nonce html field for forms
     *
     * @var string
     */
    private $nonce_field;

    /**
     * Defines namespaces for requests. Defines nonce data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $routes_namespace
     * @param string $nonce_key
     * @param string $nonce_name
     * @throws InvalidArgumentException If any arguments are empty.
     * @throws InvalidArgumentException If any arguments are not a string.
     * @throws RuntimeException If class is instantiated before WP core functions are loaded.
     */
    public function __construct(
        $routes_namespace,
        $nonce_key,
        $nonce_name
    ) {
        $i = 0;
        $paramkeys = ['routes_namespace', 'nonce_key', 'nonce_name'];
        foreach (func_get_args() as $arg) {
            if (!is_string($arg)) {
                throw new InvalidArgumentException($paramkeys[$i] . ' must be a string');
            }
    
            if (empty($arg)) {
                throw new InvalidArgumentException($paramkeys[$i] . ' must not be empty');
            }
            $i++;
        }

        if (!function_exists('wp_create_nonce')) {
            throw new RuntimeException(
                'Nonces cannot be made because WordPress core functions have not been loaded yet. 
                Try instantiating this class in the \'init\' or \'plugins_loaded\' hook.'
            );
        }

        require_once(__DIR__ . '/dist/AWR/FastRoute/bootstrap.php');
        $this->routes_namespace = $routes_namespace;
        $this->nonce_key = $nonce_key;
        $this->nonce_name = $nonce_name;
        $this->nonce = wp_create_nonce($this->nonce_name);
        $this->nonce_field = wp_nonce_field($this->nonce_name, $this->nonce_key, true, false);
        
        $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
            $r->addGroup($this->getRouteNamespace(), function (FastRoute\RouteCollector $r) {
                $this->declareRoutes($r);
            });
        });

        $this->dispatch($dispatcher);
    }

    /**
     * Parses requests and dispatches to appropriate handlers
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\Dispatcher $dispatcher
     * @return void
     */
    protected function dispatch(FastRoute\Dispatcher $dispatcher) {
        // Fetch method and URI
        $httpmethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $routeInfo = $dispatcher->dispatch($httpmethod, $uri);
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                break;
            case FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $handler($vars);
                break;
        }
    }

    /**
     * Method for declaring routes.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\RouteCollector $r
     * @return void
     */
    protected function declareRoutes(FastRoute\RouteCollector $r) {
    }

    /**
     * Adds a new route group
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\RouteCollector $r
     * @param string                   $grouppattern
     * @param callable                 $callable
     * @return void
     */
    protected function addGroup(FastRoute\RouteCollector $r, $grouppattern, $callable) {
        $r->addGroup($grouppattern, $callable);
    }

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
     * @param string                   $type 'ajax' or 'post'
     * @return void
     */
    public function add(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        $callable,
        $middlewares = [],
        $aftermiddlewares = [],
        $noncecheck = false,
        $type = 'ajax'
    ) {
        if (strtolower($type) === 'ajax') {
            $r->addRoute($method, $route, function ($args) use ($middlewares, $aftermiddlewares, $callable, $noncecheck) {
                if ($noncecheck === true) {
                    check_ajax_referer($this->nonce_name, $this->nonce_key);
                }
                $payload = $this->getJsonPayload();
                foreach ($middlewares as $middleware) {
                    call_user_func($middleware, $args, $payload);
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

        if (strtolower($type) === 'post') {
            $r->addRoute($method, $route, function ($args) use ($middlewares, $aftermiddlewares, $callable, $noncecheck) {
                if ($noncecheck === true) {
                    $nonce = '';
                    if (isset($_REQUEST[$this->nonce_key])) {
                        $nonce = sanitize_text_field(wp_unslash($_REQUEST[$this->nonce_key]));
                    }
                    if (!wp_verify_nonce($nonce, $this->nonce_name)) {
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

    /**
     * Adds a nonce verified route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::add()
     * @param FastRoute\RouteCollector $r
     * @param string|string[]          $method GET, POST, PUT, etc.
     * @param string                   $route
     * @param callable                 $callable class method or function
     * @param callable[]               $middlewares
     * @param callable[]               $aftermiddlewares
     * @param string                   $type 'ajax' or 'post'
     * @return void
     */
    public function addRoute(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        $callable,
        $middlewares = [],
        $aftermiddlewares = [],
        $type = 'ajax'
    ) {
        $this->add($r, $method, $route, $callable, $middlewares, $aftermiddlewares, true, $type);
    }

    /**
     * Adds a public (no nonce check) route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::add()
     * @param FastRoute\RouteCollector $r
     * @param string|string[]          $method GET, POST, PUT, etc.
     * @param string                   $route
     * @param callable                 $callable class method or function
     * @param callable[]               $middlewares
     * @param callable[]               $aftermiddlewares
     * @param string                   $type 'ajax' or 'post'
     * @return void
     */
    public function addPublicRoute(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        $callable,
        $middlewares = [],
        $aftermiddlewares = [],
        $type = 'ajax'
    ) {
        $this->add($r, $method, $route, $callable, $middlewares, $aftermiddlewares, false, $type);
    }

    /**
     * Adds a nonce verified POST route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @see self::add()
     * @param FastRoute\RouteCollector $r
     * @param string|string[]          $method GET, POST, PUT, etc.
     * @param string                   $route
     * @param callable                 $callable class method or function
     * @param callable[]               $middlewares
     * @param callable[]               $aftermiddlewares
     * @return void
     */
    public function addPostRoute(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        $callable,
        $middlewares = [],
        $aftermiddlewares = []
    ) {
        $this->add($r, $method, $route, $callable, $middlewares, $aftermiddlewares, true, 'post');
    }

    /**
     * Adds a public POST route
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\RouteCollector $r
     * @param string|string[]          $method GET, POST, PUT, etc.
     * @param string                   $route
     * @param callable                 $callable class method or function
     * @param callable[]               $middlewares
     * @param callable[]               $aftermiddlewares
     * @return void
     */
    public function addPublicPostRoute(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        $callable,
        $middlewares = [],
        $aftermiddlewares = []
    ) {
        $this->add($r, $method, $route, $callable, $middlewares, $aftermiddlewares, false, 'post');
    }

    /**
     * Handles redirects from other origins
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string     $url
     * @param string     $method or function
     * @param mixed|null $model
     * @param array      $getkey_conditions
     * @param array      $getkeyval_conditions
     * @param function[] $middlewares array of middleware functions
     * @return void
     */
    public function addRedirectRoute(
        $url,
        $method,
        $model = null,
        $getkey_conditions = [],
        $getkeyval_conditions = [],
        $middlewares = []
    ) {
        $redirected = false;
        $redirect_uri = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : '';
        $redirect_fullpath = isset($_SERVER['REDIRECT_URI']) ? $_SERVER['REDIRECT_URI'] : '';
        if (!empty($redirect_uri)) {
            if (strpos($url, $redirect_uri) !== false) {
                $redirected = true;
            }
        } elseif (!empty($redirect_fullpath)) {
            if (strpos($url, $redirect_fullpath) !== false) {
                $redirected = true;
            }
        }

        if ($redirected === false) {
            return;
        }

        foreach ($getkey_conditions as $getkey) {
            $getval = isset($_GET[$getkey]) ? $_GET[$getkey] : null;
            if ($getval === null) {
                return;
            }
        }

        foreach ($getkeyval_conditions as $getkey => $val) {
            $getval = isset($_GET[$getkey]) ? $_GET[$getkey] : null;
            if ($getval === $val) {
                return;
            }
        }

        foreach ($middlewares as $middleware) {
            $middleware($_GET);
        }

        if ($model === null) {
            call_user_func($method, $_GET);
        } else {
            $model->{$method}($_GET);
        }
    }

    /**
     * Decodes JSON body if set. Returns null if not set
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array|null
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

        return $payload;
    }

    /**
     * Create query url for GET request
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $route defaults to site_url()
     * @return string
     */
    public function createQueryUrl($route = '') {
        $query = [
            $this->nonce_key => $this->nonce,
        ];
        $route = !empty($route) ? $route : site_url();

        return add_query_arg($query, $route);
    }

    /**
     * Creates a POST form with a given route and inner html if provided
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string      $route
     * @param string      $innerhtml
     * @param string      $method
     * @param string|null $formid
     * @return string
     */
    public function createPostForm(
        $route,
        $innerhtml = '',
        $method = 'post',
        $formid = null
    ) {
        ob_start();
        $id = $formid !== null ? ' id="' . esc_attr($formid) . '"' : '';
        $route = !empty($route) ? $route : site_url();
        ?>
        <form action="<?php echo esc_url($route) ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
            <?php echo $this->nonce_field; ?>
            <?php echo $innerhtml ?>
        </form>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * Creates a POST form with a given route and inner html if provided
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string      $route
     * @param string      $innerhtml
     * @param string      $method
     * @param string|null $formid
     * @return string
     */
    public function createPostFormNoHiddenFieldID(
        $route,
        $innerhtml = '',
        $method = 'post',
        $formid = null
    ) {
        ob_start();
        $id = $formid !== null ? ' id="' . esc_attr($formid) . '"' : '';
        $route = !empty($route) ? $route : site_url();
        ?>
        <form action="<?php echo esc_url($route) ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
            <input
                type="hidden"
                name="<?php echo esc_attr($this->nonce_key) ?>"
                value="<?php echo wp_create_nonce($this->nonce_name) ?>"
            />
            <?php wp_referer_field(true); ?>
            <?php echo $innerhtml ?>
        </form>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * Script injection variables to be used for AJAX requests
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getScriptInjectionVariables() {
        return [
            'nonceKey' => $this->nonce_key,
            'nonce' => $this->nonce,
            'nonceField' => $this->nonce_field,
            'routeNamespace' => $this->routes_namespace,
        ];
    }

    /**
     * Returns routes_namespace appended to the site base URL
     *
     * Note that since this method uses WordPress' `get_home_url()` function,
     * this method cannot be called before WordPress core functions are loaded
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getApiEndpoint() {
        return get_home_url(null, $this->routes_namespace);
    }

    /**
     * Getter for route_namespace
     *
     * @return string
     */
    public function getRouteNamespace() {
        return $this->routes_namespace;
    }

    /**
     * Getter for nonce_key
     *
     * @return string
     */
    public function getNonceKey() {
        return $this->nonce_key;
    }

    /**
     * Getter for nonce
     *
     * @return string
     */
    public function getNonce() {
        return $this->nonce;
    }

    /**
     * Getter for nonce_name
     *
     * @return string
     */
    public function getNonceName() {
        return $this->nonce_name;
    }
    
    /**
     * Getter for nonce_field
     *
     * @return string
     */
    public function getNonceField() {
        return $this->nonce_field;
    }
}
