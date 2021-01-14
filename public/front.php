<?php

use Mlaphp\Router;

require('setup.php');

// get the router
$router = new Router(dirname(__DIR__) . '/src/pages');
$router->setRoutes(array());

//match router against url
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = $router->match($path);

require($route);

// invoke controller and send response - wiring controller and response is
$response = $controller->__invoke();
$response->send();