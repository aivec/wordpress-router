<?php
namespace Aivec\WordPress\Routing;

use AWR\FastRoute;

/**
 * Collects routes, dispatches and listens to requests
 */
class Dispatcher {

    /**
     * Router
     *
     * @var Router
     */
    private $router;

    /**
     * Constructs list of `Router`s if provided
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Router $router
     */
    public function __construct(Router $router) {
        $this->router = $router;
    }

    /**
     * Declares and dispatches routes
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function listen() {
        $router = $this->router;
        $dispatcher = $this->wordpressSimpleDispatcher(function (WordpressRouteCollector $r) use ($router) {
            $r->setNonceKey($router->getNonceKey());
            $r->setNonceName($router->getNonceName());
            $r->addGroup($this->getMultiSitePrefix() . $router->getMyRoutePrefix(), function ($r) use ($router) {
                $router->declareRoutes($r);
            });
        });

        $reqmethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST';
        
        if ($router instanceof RequestKeyRouter) {
            $reqkeyroute = isset($_REQUEST[WordPressRequestKeyRouteCollector::ROUTE_KEY]) ? $_REQUEST[WordPressRequestKeyRouteCollector::ROUTE_KEY] : '';
            $this->dispatch($dispatcher, $reqmethod, $reqkeyroute);
        } elseif (!($router instanceof RequestKeyRouter) && $router instanceof Router) {
            $requri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $this->dispatch($dispatcher, $reqmethod, $requri);
        }
    }

    /**
     * Sets up `FastRoute` classes
     *
     * @param callable $routeDefinitionCallback
     * @param array    $options
     * @return \AWR\FastRoute\Dispatcher
     */
    private function wordpressSimpleDispatcher(callable $routeDefinitionCallback, array $options = []) {
        $collectorClassName = 'WordpressRouteCollector';
        if ($this->router instanceof RequestKeyRouter) {
            $collectorClassName = 'WordpressRequestKeyRouteCollector';
        }

        $options += [
            'routeParser' => '\\AWR\\FastRoute\\RouteParser\\Std',
            'dataGenerator' => '\\AWR\\FastRoute\\DataGenerator\\GroupCountBased',
            'dispatcher' => '\\AWR\\FastRoute\\Dispatcher\\GroupCountBased',
            'routeCollector' => __NAMESPACE__ . '\\' . $collectorClassName,
        ];

        /* @var RouteCollector $routeCollector */
        $routeCollector = new $options['routeCollector'](
            new $options['routeParser'](), new $options['dataGenerator']()
        );
        $routeDefinitionCallback($routeCollector);

        return new $options['dispatcher']($routeCollector->getData());
    }

    /**
     * Parses requests and dispatches to appropriate handlers
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param FastRoute\Dispatcher $dispatcher
     * @param string               $httpmethod
     * @param string               $uri should only include url path (and optionally query args)
     * @return void
     */
    protected function dispatch(FastRoute\Dispatcher $dispatcher, $httpmethod, $uri) {
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
     * Returns trailing path if using multi-site
     *
     * 'Route prefix', in this case, means a route path that should always
     * come at the very beginning of any route you register. If using multi-site,
     * the site path will be returned. If no path is found, an empty string will be returned
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return string
     */
    public function getMultiSitePrefix() {
        $baseurip = wp_parse_url(trim(get_home_url(), '/'), PHP_URL_PATH);
        return !empty($baseurip) ? $baseurip : '';
    }
}
