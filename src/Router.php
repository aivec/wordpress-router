<?php
namespace Aivec\WordPress\Routing;

use InvalidArgumentException;
use RuntimeException;
use AWR\FastRoute as FastRoute;

/**
 * Create routes
 *
 * NOTE: This class MUST be instantiated AFTER WordPress core functions are loaded (ie. some
 * time after 'plugins_loaded', 'init', or any other appropriate WordPress hook)
 */
class Router {

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
     * @param string $nonce_key
     * @param string $nonce_name
     * @throws InvalidArgumentException If any arguments are empty.
     * @throws InvalidArgumentException If any arguments are not a string.
     * @throws RuntimeException If class is instantiated before WP core functions are loaded.
     */
    public function __construct(
        $nonce_key,
        $nonce_name
    ) {
        $i = 0;
        $paramkeys = ['nonce_key', 'nonce_name'];
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

        // bootstrap FastRoute package
        require_once(__DIR__ . '/dist/AWR/FastRoute/bootstrap.php');
        require_once(__DIR__ . '/dist/AWR/FastRoute/functions.php');
        $this->nonce_key = $nonce_key;
        $this->nonce_name = $nonce_name;
        $this->nonce = wp_create_nonce($this->nonce_name);
        $this->nonce_field = wp_nonce_field($this->nonce_name, $this->nonce_key, true, false);
    }

    /**
     * Method for declaring routes.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\RouteCollector $r
     * @return void
     */
    public function declareRoutes(FastRoute\RouteCollector $r) {
    }

    /**
     * Method for declaring redirect routes.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\RouteCollector $r
     * @return void
     */
    public function declareRedirectRoutes(FastRoute\RouteCollector $r) {
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
    public function addGroup(FastRoute\RouteCollector $r, $grouppattern, callable $callable) {
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
     * @return void
     */
    public function addRoute(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->add($r, $method, $route, $callable, $middlewares, $aftermiddlewares, true);
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
     * @return void
     */
    public function addPublicRoute(
        FastRoute\RouteCollector $r,
        $method,
        $route,
        callable $callable,
        array $middlewares = [],
        array $aftermiddlewares = []
    ) {
        $this->add($r, $method, $route, $callable, $middlewares, $aftermiddlewares, false);
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
     * @param string $route
     * @return string
     */
    public function createQueryUrl($route) {
        $query = [
            $this->nonce_key => $this->nonce,
        ];

        return add_query_arg($query, $this->getApiEndpoint($route));
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
        $route = !empty($route) ? $this->getApiEndpoint(rawurlencode($route)) : $this->getApiEndpoint();
        ?>
        <form action="<?php echo $route ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
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
        $route = !empty($route) ? $this->getApiEndpoint(rawurlencode($route)) : $this->getApiEndpoint();
        ?>
        <form action="<?php echo $route ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
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
     * Strips trailing slash from given URL
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $baseurl
     * @return string
     */
    public function stripTrailingSlash($baseurl) {
        if (false !== $pos = strrpos($baseurl, '/')) {
            if ($pos === (strlen($baseurl) - 1)) {
                // strip trailing slash
                $baseurl = substr($baseurl, 0, -1);
            }
        }

        return $baseurl;
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
        ];
    }

    /**
     * Returns `$route` appended to the site base URL
     *
     * Note that since this method uses WordPress' `get_home_url()` function,
     * this method cannot be called before WordPress core functions are loaded
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $route
     * @return string
     */
    public function getApiEndpoint($route = '') {
        return get_home_url(null, $route);
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
