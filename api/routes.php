<?php

use Phalcon\Mvc\Router;

/*
 * Define the endpoint routes here
 */
$router = new Router();

//Default route
$router->addget(
    '/',
    function () {
        echo '<h1>Thrust API</h1>';
    }
);

return $router;
