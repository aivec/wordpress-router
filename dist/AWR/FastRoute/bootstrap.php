<?php

namespace AWR\FastRoute;

require __DIR__ . '/functions.php';

spl_autoload_register(function ($class) {
    if (strpos($class, 'AWR\\FastRoute\\') === 0) {
        $name = substr($class, strlen('AWR\FastRoute'));
        require __DIR__ . strtr($name, '\\', DIRECTORY_SEPARATOR) . '.php';
    }
});
