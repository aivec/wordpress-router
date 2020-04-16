<?php
namespace Aivec\WordPress\Routing;

use RuntimeException;

/**
 * Create routes
 *
 * NOTE: This class MUST be instantiated AFTER WordPress core functions are loaded (ie. some
 * time after 'plugins_loaded', 'init', or any other appropriate WordPress hook)
 */
class Router {

    /**
     * Dispatcher instance
     *
     * @var Dispatcher
     */
    public $dispatcher;

    /**
     * WordPress nonce key for POST/AJAX requests
     *
     * @var string
     */
    private $nonce_key = '';

    /**
     * WordPress nonce name for POST/AJAX requests
     *
     * @var string
     */
    private $nonce_name = '';

    /**
     * WordPress nonce token
     *
     * @var string
     */
    private $nonce = '';

    /**
     * WordPress nonce html field for forms
     *
     * @var string
     */
    private $nonce_field = '';

    /**
     * Top-level route that all declared routes fall under
     *
     * @var string
     */
    private $myRoutePrefix;

    /**
     * Defines namespaces for requests. Defines nonce data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $routePrefix a top-level route that all declared routes will fall under.
     *                            ex: `/myroutes`
     * @param string $nonce_key
     * @param string $nonce_name
     */
    public function __construct(
        $routePrefix,
        $nonce_key = '',
        $nonce_name = ''
    ) {
        if (strpos($routePrefix, '/') !== 0) {
            $routePrefix = '/' . $routePrefix;
        }
        $this->myRoutePrefix = $routePrefix;
        if (!empty($nonce_key) && !empty($nonce_name)) {
            $this->setNonce($nonce_key, $nonce_name);
        }

        $this->dispatcher = new Dispatcher($this);
    }

    /**
     * Sets nonce data
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param mixed $nonce_key
     * @param mixed $nonce_name
     * @return void
     * @throws RuntimeException If called before WP core functions are loaded.
     */
    public function setNonce($nonce_key, $nonce_name) {
        if (!function_exists('wp_create_nonce')) {
            throw new RuntimeException(
                'Nonces cannot be made because WordPress core functions have not been loaded yet. 
                Try instantiating this class in the \'init\' or \'plugins_loaded\' hook.'
            );
        }

        $this->nonce_key = $nonce_key;
        $this->nonce_name = $nonce_name;
        $this->nonce = wp_create_nonce($this->nonce_name);
        $this->nonce_field = wp_nonce_field($this->nonce_name, $this->nonce_key, true, false);
    }

    /**
     * Method for declaring routes.
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param WordPressRouteCollector $r
     * @return void
     */
    public function declareRoutes(WordPressRouteCollector $r) {
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
     * Script injection variables to be used for AJAX requests
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return array
     */
    public function getScriptInjectionVariables() {
        return [
            'endpoint' => trim(get_home_url(), '/'),
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

    /**
     * Getter for `myRoutePrefix`
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getMyRoutePrefix() {
        return $this->myRoutePrefix;
    }
}
