<?php

use Phalcon\Loader;
use Phalcon\Di;
use Phalcon\DI\FactoryDefault;

//Autoload the models
$loader = new Loader();
$loader->registerNamespaces(
    [
        'Api\Models' => '/models/',
    ]
);
$loader->register();

//Setup the DI & load routes
$di = new FactoryDefault();
$di->set('router', function () {
    return require 'routes.php';
});