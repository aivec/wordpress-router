# WordPress REST Router

This package provides a routing library for WordPress with WordPress specific wrappers such as nonce verification and user role checking. The backbone of this package uses [FastRoute](https://github.com/nikic/FastRoute), a small and succinct route resolver. `FastRoute` is also the route resolver used by the popular micro-framework [Slim](http://www.slimframework.com/).

## The Problem

Routing in WordPress is a pain for plugin authors. It relies solely on `$_POST` object keys to resolve routes if you go with WordPress' traditional way of registering AJAX handlers via `admin-ajax.php`. You could use WordPress' [REST APIs](https://developer.wordpress.org/rest-api/), but you don't have control of _when_ routes are resolved. This is important to developers who create extensions for other plugins where the load order is out of their control. This package also differs from WordPress' implementation in that it doesn't provide `validate` and `sanitize` callbacks, opting instead for generic middlewares.

## Features

This library provides many features to streamline the provisioning of routes, as well as some optional default middlewares. The main features are as follows:

- Role based route registration (editor, administrator, etc.)
- Automatic nonce verification
- URL parameters (**NOT REJEX** :grin:)
- Passthru routing (non-AJAX routes)
- Helpers for generating HTML forms
- JWT route registration
- JWT settings page for automatic key pair generation

## Installation

Install with [composer](https://getcomposer.org/):

```sh
$ composer require aivec/wordpress-router
```

If you plan on using this package in a plugin, we _highly_ recommend namespacing it with [mozart](https://github.com/coenjacobs/mozart). If you don't, things may break in an impossible to debug way. [You have been warned](https://wptavern.com/a-narrative-of-using-composer-in-a-wordpress-plugin).

## Usage Guide

- [Public Route](#public-route)
  - [Calling the Public Route](#calling-the-public-route)
- [Private Route](#private-route)
  - [Calling the Private Route](#calling-the-private-route)
- [URL Parameters](#url-parameters)
- [Form Data](#form-data)
- [Making Everything Easier](#making-everything-easier)

## Public Route

A public route refers to a route without nonce verification. A public route is accessible by anyone, from anywhere.

```php
use Aivec\WordPress\Routing\Router;
use Aivec\WordPress\Routing\WordPressRouteCollector;

// First, we declare our routes by extending the `Router` class:
class Routes extends Router {

    /**
     * This is where we define each route
     */
    public function declareRoutes(WordPressRouteCollector $r) {
        $r->addPublicRoute('GET', '/hamburger', function () {
            return 'Here is a public hamburger.';
        });
    }
}

// Next, we instantiate the `Routes` class with a unique namespace and listen for requests
$routes = new Routes('/mynamespace');
$routes->dispatcher->listen();
```

### Calling the Public Route

You can test the route from the command line, like so:

```sh
$ curl -X GET http://www.my-site.com/mynamespace/hamburger
'Here is a public hamburger.'
```

Or, you can use `jQuery`'s `ajax` function to send a request from a script loaded into a WordPress page:

```js
jQuery.ajax("http://www.my-site.com/mynamespace/hamburger", {
  success(data) {
    var response = JSON.parse(data);

    console.log(response); // Here is a public hamburger.
  },
});
```

## Private Route

A private route refers to a route with nonce verification.

```php
use Aivec\WordPress\Routing\Router;
use Aivec\WordPress\Routing\WordPressRouteCollector;

// First, extend the `Router` class:
class Routes extends Router {

    /**
     * This is where we define each route
     */
    public function declareRoutes(WordPressRouteCollector $r) {
        /**
         * `add` is the default way to register a route with nonce verification
         */
        $r->add('POST', '/hamburger', function () {
            return 'Here is a private hamburger.';
        });
    }
}

```

After declaring our routes, we instantiate the `Routes` class with a unique namespace.

This time, we pass in a nonce key and nonce name as the second and
third argument, respectively.

Since nonce handling requires WordPress core functions, we must instantiate the `Routes`
class after core functions have been loaded. You can use the `init` hook, or any other
appropriate hook to ensure core functions are loaded.

```php
$routes = null;
add_action('init', function () use ($routes) {
    $routes = new Routes('/mynamespace', 'nonce-key', 'nonce-name');
    $routes->dispatcher->listen();
});
```

### Calling the Private Route

In general, private routes are called via AJAX from a JavaScript file on the WordPress site. To do this, we must make the nonce available to the script in which we want to call the route.

Leveraging `wp_localize_script`, we can use a helper method from the `Routes` class to inject the nonce variables:

```php

add_action('wp_enqueue_scripts', function () use ($routes) {
    wp_enqueue_script(
        'my-script',
        site_url() . '/wp-content/plugins/my-plugin/my-script.js',
        [],
        '1.0.0',
        false
    );

    wp_localize_script('my-script', 'myvars', $routes->getScriptInjectionVariables());
});
```

Now, `my-script.js` will have the nonce variables we need to make the call.

```js
// my-script.js
jQuery.ajax(`${myvars.endpoint}/hamburger`, {
  method: "POST",
  data: {
    [myvars.nonceKey]: myvars.nonce,
  },
  success(data) {
    var response = JSON.parse(data);

    console.log(response); // Here is a private hamburger.
  },
});
```

## URL Parameters

Curly braces are used to define a URL parameter.

URL parameters are parsed and then inserted into an `$args` variable, which is always the _first_ parameter given to the handler function.

```php
$r->add('POST', '/hamburger/{burgername}', function (array $args) {
    return 'Here is a ' . $args['burgername'] . ' hamburger.';
});
```

```js
// my-script.js
jQuery.ajax(`${myvars.endpoint}/hamburger/mushroom`, {
  method: "POST",
  data: {
    [myvars.nonceKey]: myvars.nonce,
  },
  success(data) {
    var response = JSON.parse(data);

    console.log(response); // Here is a mushroom hamburger.
  },
});
```

You can define as many parameters as you want.

```php
$r->add('POST', '/hamburger/{burgername}/{count}', function (array $args) {
    return 'Here are ' . $args['count'] . ' ' . $args['burgername'] . ' hamburgers.';
});
```

You can also limit the type of parameter accepted, as well as provide your own patterns for more granular control.

```php
// Matches /user/42, but not /user/xyz
$r->add('POST', '/user/{id:\d+}', .....);

// Matches /user/foobar, but not /user/foo/bar
$r->add('GET', '/user/{name}', .....);

// Matches /user/foo/bar as well
$r->add('GET', '/user/{name:.+}', .....);
```

There are many possibilities for route definitions. For detailed information about how routes are resolved, refer [here](https://github.com/nikic/FastRoute#defining-routes).

## Form Data

The router expects `POST` requests to be sent with a content type of `application/x-www-form-urlencoded`. Form data is sent as a JSON encoded string as the value of a `payload` key in the body of the request.

```php
// $payload contains the decoded JSON key-value array
$r->add('POST', '/hamburger', function (array $args, array $payload) {
    $ingredients = join(' and ', $payload['ingredients']);
    return 'I want ' . $ingredients . ' on my hamburger.';
});
```

```js
// my-script.js
jQuery.ajax(`${myvars.endpoint}/hamburger`, {
  method: "POST",
  data: {
    [myvars.nonceKey]: myvars.nonce,
    payload: JSON.stringify({
      ingredients: ["pickles", "onion"],
    }),
  },
  success(data) {
    var response = JSON.parse(data);

    console.log(response); // I want pickles and onion on my hamburger.
  },
});
```

## Making Everything Easier

As we've seen above, private routes require a nonce key-value pair to be present in the body of a `POST` request. You may have noticed that we excluded `GET` requests in those examples. This is because `GET` requests don't have body content, which means that the nonce variables must be set as URL query parameters. This whole process is tedious, and we can do better.

For people transpiling their JavaScript, we recommend using [axios](https://github.com/axios/axios) with our [helper library](https://github.com/aivec/reqres-utils). This completely abstracts nonce handling and JSON encoding, as well as automatically setting nonce variables in the request regardless of the request method (`GET`, `POST`, `PUT`, etc.).

The following is the [Form Data](#form-data) example, rewritten using these libraries:

```js
// my-script.js
import axios from "axios";
import { createRequestBody } from "@aivec/reqres-utils";

axios
  .post(
    `${myvars.endpoint}/hamburger`,
    createRequestBody(myvars, {
      ingredients: ["pickles", "onion"],
    })
  )
  .then(({ data }) => {
    console.log(data);
  });
```
