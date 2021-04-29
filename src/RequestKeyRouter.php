<?php

namespace Aivec\WordPress\Routing;

/**
 * This class resolves routes by checking the value of the `$_REQUEST` object key `awr_req_route`.
 * This allows for creating routes that you want to be resolved before a certain page is loaded
 * without changing the URI of the page. As such, this class is not for creating AJAX endpoints.
 */
class RequestKeyRouter extends Router
{
    /**
     * Create query url for GET request
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $route
     * @param string $baseurl defaults to `get_home_url()`
     * @param array  $queryargs
     * @return string
     */
    public function createQueryUrl($route, $baseurl = '', array $queryargs = []) {
        $route = $this->getMyRoutePrefix() . '/' . trim($route, '/');
        $query = array_merge(
            [
                $this->getNonceKey() => $this->getNonce(),
                WordPressRequestKeyRouteCollector::ROUTE_KEY => rawurlencode($route),
            ],
            $queryargs
        );

        $baseurl = !empty($baseurl) ? $baseurl : get_home_url();
        return add_query_arg($query, $baseurl);
    }

    /**
     * Creates a public (no nonce check) query url for GET request
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param string $route
     * @param string $baseurl defaults to `get_home_url()`
     * @param array  $queryargs
     * @return string
     */
    public function createPublicQueryUrl($route, $baseurl = '', array $queryargs = []) {
        $route = $this->getMyRoutePrefix() . '/' . trim($route, '/');
        $query = array_merge(
            [
                WordPressRequestKeyRouteCollector::ROUTE_KEY => rawurlencode($route),
            ],
            $queryargs
        );

        $baseurl = !empty($baseurl) ? $baseurl : get_home_url();
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
        $actionurl = !empty($actionurl) ? $actionurl : get_home_url();
        $route = $this->getMyRoutePrefix() . '/' . trim($route, '/');
        ?>
        <form action="<?php echo esc_url($actionurl) ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
            <input type="hidden" name="<?php echo WordPressRequestKeyRouteCollector::ROUTE_KEY ?>" value="<?php echo rawurlencode($route) ?>" />
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
        $actionurl = !empty($actionurl) ? $actionurl : get_home_url();
        $route = $this->getMyRoutePrefix() . '/' . trim($route, '/');
        ?>
        <form action="<?php echo esc_url($actionurl) ?>" method="<?php echo esc_attr($method) ?>"<?php echo $id ?>>
            <input type="hidden" name="<?php echo WordPressRequestKeyRouteCollector::ROUTE_KEY ?>" value="<?php echo rawurlencode($route) ?>" />
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
