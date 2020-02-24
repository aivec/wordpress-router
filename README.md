# WordPress REST Router
This package provides a routing library for WordPress with WordPress specific wrappers such as nonce verification and admin role checking. The backbone of this package uses [FastRoute](https://github.com/nikic/FastRoute), a small and succinct route resolver. `FastRoute` is also the route resolver used by the popular micro-framework [Slim](http://www.slimframework.com/).

## The Problem
Routing in WordPress is terrible. It relies solely on `$_POST` object keys to resolve routes if you go with their traditional way of registering AJAX handlers. You could use WordPress' relatively new [REST APIs](https://developer.wordpress.org/rest-api/), but I've foregone that option because it requires that you [know the permalink settings upfront](https://developer.wordpress.org/rest-api/key-concepts/). It also doesn't provide generic middleware handling, instead opting to provide only 'validate' and 'sanitize' callbacks. They also use large options arrays as arguments to functions rather than providing named methods (why do they always do this? You get *NO intellisense* with this approach). I also have a number of personal gripes with WP standards... (not PSR-4 compliant, absolutely dreadful styling rules, etc.)

## Installation
Install with [composer](https://getcomposer.org/):
```sh
$ composer require aivec/wordpress-router
```
If you plan on using this package in a plugin, I *highly* recommend namespacing it with [mozart](https://github.com/coenjacobs/mozart). If you don't, things may break in an impossible to debug way. [You have been warned](https://wptavern.com/a-narrative-of-using-composer-in-a-wordpress-plugin).

## A Short Example
Lets add a public route:
```php
class Routes extends Aivec\WordPress\Routing\Router {
    protected function declareRoutes($r) {
        $this->addPublicRoute($r, 'POST', '/brownies/{flavor}', function ($args) {
            return 'I like ' . $args['flavor'] . ' brownies';
        });
    }
}

add_action('init', function () {
    $routes = new Routes('/mynamespace/api/v1', 'nonce-key', 'nonce-name')
});
```
Now test the route:
```sh
$ curl -X POST my-site.com/mynamespace/api/v1/brownies/chocolate
'I like chocolate brownies'
```

## Usage
### Creating Routes
Routes can be created by overriding the `declareRoutes` method of the `Router` class:
```php
class Routes extends Aivec\WordPress\Routing\Router {
    protected function declareRoutes($r) {
        // all routes go here

        /*
         * addRoute adds a route that includes nonce verification.
         * 
         * By default, addRoute creates an AJAX route. An AJAX route expects a value 
         * to be returned by the callable. If the value returned is not empty,
         * addRoute will die with the result:
         * 
         * die('this is a cake')
         * 
         * If nothing is returned or the return value is empty, die(0) will be called
         */
        $this->addRoute($r, 'GET', '/getcake/withnonce', function ($args) {
            return 'this is a cake';
        });

        /* 
         * addPublicRoute adds a route that can be accessed by anybody
         */
        $this->addPublicRoute($r, 'POST', '/makeAjaxCake/{cakeFlavor}', function ($args) {
            return 'heres a ' . $args['cakeFlavor'] . ' flavored cake';
        });

        /*
         * POST routes can also be declared. POST routes are the same as AJAX
         * routes except that the nonce verification is for POST requests and
         * the handler does not do anything with the return value.
         */
        $this->addPostRoute($r, ['PUT', 'POST'], '/makePostCake/withnonce', function ($args) {
            // some database operation...
        });

        /*
         * public POST routes are also declarable
         */
        $this->addPublicPostRoute($r, 'POST', '/makePostCake/{cakeFlavor}', function ($args) {
            // some database operation...
        });

        /*
         * Route groups can also be added
         */
        $this->addGroup($r, '/candy', function ($r) {
            $this->addRoute($r, 'GET', '/bubblegum', function ($args) {
                return 'Im the /candy/bubblegum route';
            });
            $this->addRoute($r, 'GET', '/airheads', function ($args) {
                return 'Im the /candy/airheads route';
            });
        });
        
        /*
         * An array of middleware callables that run before and after route invokation
         * can be passed in as arguments
         *
         * The final result is 'Im the /airheads route modified'
         *
         * NOTE: you can easily stop propagation in your middleware function
         * by calling die()
         */
        $this->addPublicRoute($r, 'GET', '/airheads', function ($args) {
            return 'Im the /airheads route';
        }, [
            function ($args) {
                // do some validation
            },
        ],
        [
            function ($res, $args) {
                return $res . ' modified';
            },
        ]);
    }
}
```
Detailed information about how routes are resolved can be found [here](https://github.com/nikic/FastRoute#defining-routes).

After creating your routes, instantiate the class with your route namespace:
```php
/*
 * WARNING: you MUST instantiate the class sometime after WordPress core functions are
 * loaded ('plugins_loaded', 'init', etc.).
 */
add_action('init', function () {
    // 'routegroup' can be any route of your choice. 'nonce-key' and 'nonce-name' are also arbitrary.
    $routes = new Routes('/routegroup', 'nonce-key', 'nonce-name')
});
```

### HTML Forms
Nonce-included HTML forms can be created for a route:
```php
$form = $routes->createPostForm(
    'https://my-site.com/routegroup/makePostCake/strawberry', // the route
    '<input type="hidden" name="myFormField" value="myFormValue" />', // the inner-html for the form
    'post', // the form request type ('post', 'put', etc.)
    'myformid' // OPTIONAL: id of form
)
```

## Contributing
Using a special development-only `composer-dev.json` is required when building this library. Use the below command each time **BEFORE YOU COMMIT**
```bash
$ export COMPOSER=composer-dev.json && composer install --no-dev && composer build
```
### Why are the `vendor` and `dist` directories version controlled?
Because this library packages a namespaced version of `FastRoute`, and the tool for accomplishing this, [mozart](github.com/coenjacobs/mozart), cannot do it automatically.

Long answer: when this library is included as a composer dependency in another project that uses `mozart`, `mozart` will attempt to recursively namespace this package, *as well as this packages dependencies*. In this case, that dependency is `FastRoute`. Even though `mozart` can successfully bundle certain packages without any manual tweaks, unfortunately `FastRoute` is not one such package. Because of this, we have to package an already bundled version of `FastRoute` and make sure that our `composer.json` does not include an autoload reference to it. Only then is it possible to require this package from another plugin/package that uses `mozart` without any manual changes.