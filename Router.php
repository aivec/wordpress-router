<?php
namespace Aivec\WordPress\Routing;

/**
 * Request handler factory
 *
 * NOTE: This class MUST be instantiated AFTER WordPress core functions are loaded (ie. some
 * time after 'plugins_loaded', 'init', or any other appropriate WordPress hook)
 */
class Router extends Dispatcher {

    /**
     * AJAX request POST key that acts as a namespace for request handling
     *
     * @var string
     */
    private $ajax_namespace;

    /**
     * POST request POST key that acts as a namespace for request handling
     *
     * @var string
     */
    private $post_namespace;

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
     * @param string $ajax_namespace
     * @param string $post_namespace
     * @param string $nonce_key
     * @param string $nonce_name
     */
    public function __construct(
        $ajax_namespace,
        $post_namespace,
        $nonce_key,
        $nonce_name
    ) {
        $this->ajax_namespace = $ajax_namespace;
        $this->post_namespace = $post_namespace;
        $this->nonce_key = $nonce_key;
        $this->nonce_name = $nonce_name;
        $this->nonce = wp_create_nonce($this->nonce_name);
        $this->nonce_field = wp_nonce_field($this->nonce_name, $this->nonce_key, true, false);
    }

    /**
     * Delegates requests to the appropriate class handler method
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string     $route
     * @param string     $method or function
     * @param mixed|null $model
     * @param function[] $middlewares array of middleware functions
     * @param string     $namespace namespace of route. allows routes with same name
     *                              to be differentiated from one another
     * @return void
     */
    public function add(
        $route,
        $method,
        $model = null,
        $middlewares = [],
        $namespace = 'default'
    ) {
        if (isset($_REQUEST[$this->ajax_namespace]) && !empty($_REQUEST[$this->ajax_namespace])) {
            if (isset($_REQUEST['route'])) {
                $n = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : $namespace;
                if ($_REQUEST['route'] === $route && $n === $namespace) {
                    check_ajax_referer($this->nonce_name, $this->nonce_key);
                    $payload = $this->getJsonPayload();
                    foreach ($middlewares as $middleware) {
                        $middleware($payload);
                    }
                    $this->processAJAX($method, $model, $payload);
                }
            }
        }

        if (isset($_REQUEST[$this->post_namespace]) && !empty($_REQUEST[$this->post_namespace])) {
            if (isset($_REQUEST['route'])) {
                $n = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : $namespace;
                if ($_REQUEST['route'] === $route && $n === $namespace) {
                    $nonce = '';
                    if (isset($_REQUEST[$this->nonce_key])) {
                        $nonce = sanitize_text_field(wp_unslash($_REQUEST[$this->nonce_key]));
                    }
                    if (!wp_verify_nonce($nonce, $this->nonce_name)) {
                        die('Security check');
                    }
                    $payload = $this->getJsonPayload();
                    foreach ($middlewares as $middleware) {
                        $middleware($payload);
                    }
                    $this->processPOST($method, $model, $payload);
                }
            }
        }
    }

    /**
     * Same as add but skips nonce verification.
     *
     * Useful for cross-origin request routing.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string     $route
     * @param string     $method or function
     * @param mixed|null $model
     * @param function[] $middlewares array of middleware functions
     * @param string     $namespace namespace of route. allows routes with same name
     *                              to be differentiated from one another
     * @return void
     */
    public function addPublicRoute(
        $route,
        $method,
        $model = null,
        $middlewares = [],
        $namespace = 'default'
    ) {
        if (isset($_REQUEST[$this->ajax_namespace]) && !empty($_REQUEST[$this->ajax_namespace])) {
            if (isset($_REQUEST['route'])) {
                $n = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : $namespace;
                if ($_REQUEST['route'] === $route && $n === $namespace) {
                    $payload = $this->getJsonPayload();
                    foreach ($middlewares as $middleware) {
                        $middleware($payload);
                    }
                    $this->processAJAX($method, $model, $payload);
                }
            }
        }

        if (isset($_REQUEST[$this->post_namespace]) && !empty($_REQUEST[$this->post_namespace])) {
            if (isset($_REQUEST['route'])) {
                $n = isset($_REQUEST['namespace']) ? $_REQUEST['namespace'] : $namespace;
                if ($_REQUEST['route'] === $route && $n === $namespace) {
                    $payload = $this->getJsonPayload();
                    foreach ($middlewares as $middleware) {
                        $middleware($payload);
                    }
                    $this->processPOST($method, $model, $payload);
                }
            }
        }
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
     * @param string  $route
     * @param string  $namespace defaults to 'default'
     * @param string  $url defaults to site_url()
     * @param boolean $ajax defaults to false
     * @return string
     */
    public function createQueryUrl($route, $namespace = '', $url = '', $ajax = false) {
        $query = [
            $this->nonce_key => $this->nonce,
            'namespace' => !empty($namespace) ? $namespace : 'default',
            'route' => $route,
        ];
        if ($ajax === true) {
            $query[$this->ajax_namespace] = 1;
        } else {
            $query[$this->post_namespace] = 1;
        }
        $url = !empty($url) ? $url : site_url();

        return add_query_arg($query, $url);
    }

    /**
     * Creates a POST form with a given route and inner html if provided
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string      $route
     * @param string      $innerhtml
     * @param string      $namespace route namespace
     * @param string      $url
     * @param string|null $formid
     * @return string
     */
    public function createPostForm(
        $route,
        $innerhtml = '',
        $namespace = 'default',
        $url = '',
        $formid = null
    ) {
        ob_start();
        $id = $formid !== null ? ' id="' . esc_attr($formid) . '"' : '';
        $url = !empty($url) ? $url : site_url();
        ?>
        <form action="<?php echo esc_url($url) ?>" method="post"<?php echo $id?>>
            <?php echo $this->nonce_field; ?>
            <input type="hidden" name="<?php echo $this->post_namespace ?>" value="1" />
            <input type="hidden" name="route" value="<?php echo $route ?>" />
            <input type="hidden" name="namespace" value="<?php echo $namespace ?>" />
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
     * @param string      $namespace route namespace
     * @param string      $url
     * @param string|null $formid
     * @return string
     */
    public function createPostFormNoHiddenFieldID(
        $route,
        $innerhtml = '',
        $namespace = 'default',
        $url = '',
        $formid = null
    ) {
        ob_start();
        $id = $formid !== null ? ' id="' . esc_attr($formid) . '"' : '';
        $url = !empty($url) ? $url : site_url();
        ?>
        <form action="<?php echo esc_url($url) ?>" method="post"<?php echo $id?>>
            <input
                type="hidden"
                name="<?php echo esc_attr($this->nonce_key) ?>"
                value="<?php echo wp_create_nonce($this->nonce_name) ?>"
            />
            <?php wp_referer_field(true); ?>
            <input type="hidden" name="<?php echo $this->post_namespace ?>" value="1" />
            <input type="hidden" name="route" value="<?php echo $route ?>" />
            <input type="hidden" name="namespace" value="<?php echo $namespace ?>" />
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
        return array(
            'nonceKey' => $this->nonce_key,
            'nonce' => $this->nonce,
            'nonceField' => $this->nonce_field,
            'ajaxNamespace' => $this->ajax_namespace,
            'postNamespace' => $this->post_namespace,
        );
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

    /**
     * Getter for ajax_namespace
     *
     * @return string
     */
    public function getAjaxNamespace() {
        return $this->ajax_namespace;
    }

    /**
     * Getter for post_namespace
     *
     * @return string
     */
    public function getPostNamespace() {
        return $this->post_namespace;
    }
}
