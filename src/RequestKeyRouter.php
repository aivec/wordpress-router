<?php
namespace Aivec\WordPress\Routing;

use AWR\FastRoute as FastRoute;

/**
 * This class resolves routes by checking the value of the `$_REQUEST` object key `awr_req_route`.
 * This allows for creating routes that you want to be resolved before a certain page is loaded
 * without changing the URI of the page. As such, this class is not for creating AJAX endpoints.
 *
 * NOTE: This class MUST be instantiated AFTER WordPress core functions are loaded (ie. some
 * time after `plugins_loaded`, `init`, or any other appropriate WordPress hook)
 */
class RequestKeyRouter extends Router {

    const ROUTE_KEY = 'awr_req_route';

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

    /**
     * Handles redirects from other origins
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string     $url
     * @param callable   $callable
     * @param array      $getkey_conditions
     * @param array      $getkeyval_conditions
     * @param callable[] $middlewares array of middleware functions
     * @return void
     */
    public function addRedirectRoute(
        $url,
        callable $callable,
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
            call_user_func($middleware, $_GET);
        }

        call_user_func($callable, $_GET);
    }

    /**
     * Create query url for GET request
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $route
     * @param string $baseurl defaults to `get_home_url()`
     * @return string
     */
    public function createQueryUrl($route, $baseurl = '') {
        $query = [
            $this->getNonceKey() => $this->getNonce(),
            self::ROUTE_KEY => rawurlencode($route),
        ];
        $baseurl = !empty($baseurl) ? $this->stripTrailingSlash($baseurl) : $this->stripTrailingSlash(get_home_url());

        return add_query_arg($query, $baseurl);
    }

    /**
     * Creates a POST form with a given route and inner html if provided
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string      $route
     * @param string      $actionurl
     * @param string      $innerhtml
     * @param string      $method
     * @param string|null $formid
     * @return string
     */
    public function createPostForm(
        $route,
        $actionurl = '',
        $innerhtml = '',
        $method = 'post',
        $formid = null
    ) {
        ob_start();
        $id = $formid !== null ? ' id="' . esc_attr($formid) . '"' : '';
        $actionurl = !empty($actionurl) ? $actionurl : $this->stripTrailingSlash(get_home_url());
        ?>
        <form action="<?php echo esc_url($actionurl) ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
            <input type="hidden" name="<?php echo self::ROUTE_KEY ?>" value="<?php echo rawurlencode($route) ?>" />
            <?php echo $this->getNonceField(); ?>
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
     * @param string      $actionurl
     * @param string      $innerhtml
     * @param string      $method
     * @param string|null $formid
     * @return string
     */
    public function createPostFormNoHiddenFieldID(
        $route,
        $actionurl = '',
        $innerhtml = '',
        $method = 'post',
        $formid = null
    ) {
        ob_start();
        $id = $formid !== null ? ' id="' . esc_attr($formid) . '"' : '';
        $actionurl = !empty($actionurl) ? $actionurl : $this->stripTrailingSlash(get_home_url());
        ?>
        <form action="<?php echo esc_url($actionurl) ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
            <input type="hidden" name="<?php echo self::ROUTE_KEY ?>" value="<?php echo rawurlencode($route) ?>" />
            <input
                type="hidden"
                name="<?php echo esc_attr($this->getNonceKey()) ?>"
                value="<?php echo wp_create_nonce($this->getNonceName()) ?>"
            />
            <?php wp_referer_field(true); ?>
            <?php echo $innerhtml ?>
        </form>
        <?php
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
