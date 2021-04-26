<?php

namespace Aivec\WordPress\Routing;

/**
 * Collects routes, dispatches and listens to requests
 */
class Dispatcher
{
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
            $prefix = '';
            if (!($router instanceof RequestKeyRouter) && $this->router->routeMustBeQueryVar() === false) {
                $prefix = $this->router->getHomeUrlPath();
            }
            $r->addGroup($prefix . $router->getMyRoutePrefix(), function ($r) use ($router) {
                $router->declareRoutes($r);
            });
        });

        $reqmethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST';

        if ($router instanceof RequestKeyRouter) {
            $reqkeyroute = isset($_REQUEST[WordPressRequestKeyRouteCollector::ROUTE_KEY]) ? $_REQUEST[WordPressRequestKeyRouteCollector::ROUTE_KEY] : '';
            $this->dispatch($dispatcher, $reqmethod, $reqkeyroute);
        } elseif (!($router instanceof RequestKeyRouter) && $router instanceof Router) {
            $requri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if ($this->router->routeMustBeQueryVar()) {
                $requri = isset($_REQUEST[WordPressRouteCollector::ROUTE_KEY]) ? $_REQUEST[WordPressRouteCollector::ROUTE_KEY] : '';
            }
            $this->dispatch($dispatcher, $reqmethod, $requri);
        }
    }

    /**
     * Sets up `FastRoute` classes
     *
     * @param callable $routeDefinitionCallback
     * @param array    $options
     * @return \FastRoute\Dispatcher
     */
    private function wordpressSimpleDispatcher(callable $routeDefinitionCallback, array $options = []) {
        $collectorClassName = 'WordPressRouteCollector';
        if ($this->router instanceof RequestKeyRouter) {
            $collectorClassName = 'WordPressRequestKeyRouteCollector';
        }

        $options += [
            'routeCollector' => __NAMESPACE__ . '\\' . $collectorClassName,
        ];

        /* @var RouteCollector $routeCollector */
        $routeCollector = new $options['routeCollector'](
            new \FastRoute\RouteParser\Std(),
            new \FastRoute\DataGenerator\GroupCountBased()
        );
        $routeDefinitionCallback($routeCollector);

        return new \FastRoute\Dispatcher\GroupCountBased($routeCollector->getData());
    }

    /**
     * Parses requests and dispatches to appropriate handlers
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param \FastRoute\Dispatcher $dispatcher
     * @param string                $httpmethod
     * @param string                $uri should only include url path (and optionally query args)
     * @return void
     */
    protected function dispatch(\FastRoute\Dispatcher $dispatcher, $httpmethod, $uri) {
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        $routeInfo = $dispatcher->dispatch($httpmethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                break;
            case \FastRoute\Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                $handler($vars);
                break;
        }
    }
}
