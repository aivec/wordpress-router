<?php
namespace Aivec\WordPress\Routing;

use Exception;
use AWR\FastRoute as FastRoute;

/**
 * Collects routes, dispatches and listens to requests
 */
class Dispatcher {

    /**
     * Array of routers
     *
     * @var Router[]
     */
    private $routers = [];

    /**
     * Constructs list of `Router`s if provided
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Router ...$routers
     * @throws Exception If any of `$routers` is not an instance of `Router`.
     */
    public function __construct(Router ...$routers) {
        foreach ($routers as $router) {
            $this->routers[] = $router;
        }
    }

    /**
     * Adds `$router` to list of routers
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @param Router $routerInstance
     * @return void
     */
    public function addRouter(Router $routerInstance) {
        $this->routers[] = $routerInstance;
    }

    /**
     * Declares and dispatches routes
     *
     * @author Evan D Shaw <evandanielshaw@gmail.com>
     * @return void
     */
    public function listen() {
        $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
            foreach ($this->routers as $router) {
                $router->declareRoutes($r);
            }
            foreach ($this->routers as $router) {
                $router->declareRedirectRoutes($r);
            }
        });

        $reqmethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST';
        $requri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $redirecturi = isset($_SERVER['REDIRECT_URI']) ? $_SERVER['REDIRECT_URI'] : '';
        $reqkeyroute = isset($_REQUEST[RequestKeyRouter::ROUTE_KEY]) ? $_REQUEST[RequestKeyRouter::ROUTE_KEY] : '';

        $this->dispatch($dispatcher, $reqmethod, $requri);
        $this->dispatch($dispatcher, $reqmethod, $redirecturi);
        $this->dispatch($dispatcher, $reqmethod, $reqkeyroute);
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
}
