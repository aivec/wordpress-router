<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbb28550d3cde08758a8b1fa94ed98f08
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'Aivec\\WordPress\\Routing\\' => 24,
            'AWR\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Aivec\\WordPress\\Routing\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'AWR\\' => 
        array (
            0 => __DIR__ . '/../..' . '/dist/AWR',
        ),
    );

    public static $classMap = array (
        'AWR\\FastRoute\\BadRouteException' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/BadRouteException.php',
        'AWR\\FastRoute\\DataGenerator' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/DataGenerator.php',
        'AWR\\FastRoute\\DataGenerator\\CharCountBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/DataGenerator/CharCountBased.php',
        'AWR\\FastRoute\\DataGenerator\\GroupCountBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/DataGenerator/GroupCountBased.php',
        'AWR\\FastRoute\\DataGenerator\\GroupPosBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/DataGenerator/GroupPosBased.php',
        'AWR\\FastRoute\\DataGenerator\\MarkBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/DataGenerator/MarkBased.php',
        'AWR\\FastRoute\\DataGenerator\\RegexBasedAbstract' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/DataGenerator/RegexBasedAbstract.php',
        'AWR\\FastRoute\\Dispatcher' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Dispatcher.php',
        'AWR\\FastRoute\\Dispatcher\\CharCountBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Dispatcher/CharCountBased.php',
        'AWR\\FastRoute\\Dispatcher\\GroupCountBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Dispatcher/GroupCountBased.php',
        'AWR\\FastRoute\\Dispatcher\\GroupPosBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Dispatcher/GroupPosBased.php',
        'AWR\\FastRoute\\Dispatcher\\MarkBased' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Dispatcher/MarkBased.php',
        'AWR\\FastRoute\\Dispatcher\\RegexBasedAbstract' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Dispatcher/RegexBasedAbstract.php',
        'AWR\\FastRoute\\Route' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/Route.php',
        'AWR\\FastRoute\\RouteCollector' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/RouteCollector.php',
        'AWR\\FastRoute\\RouteParser' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/RouteParser.php',
        'AWR\\FastRoute\\RouteParser\\Std' => __DIR__ . '/../..' . '/dist/AWR/FastRoute/RouteParser/Std.php',
        'Aivec\\WordPress\\Routing\\Router' => __DIR__ . '/../..' . '/src/Router.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbb28550d3cde08758a8b1fa94ed98f08::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbb28550d3cde08758a8b1fa94ed98f08::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbb28550d3cde08758a8b1fa94ed98f08::$classMap;

        }, null, ClassLoader::class);
    }
}
