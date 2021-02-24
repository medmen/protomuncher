<?php

use Medoo\Medoo;
use Mlaphp\Router;
use protomuncher\classes\ConfigObject;
use protomuncher\classes\ConfigurationManager;

require('setup.php');


// make sure to load a modality so we can load it's config
$geraet = 1;
if (isset($_GET['geraet']) and is_int($_GET['geraet']) and ($_GET['geraet'] > 0)) {
    $geraet = intval($_GET['geraet']);
} // switch to session here?

$config = new ConfigObject($geraet);

$db = dirname(__DIR__) . '/conf/config.sqlite';
$database = new Medoo([
    'database_type' => 'sqlite',
    'database_file' => $db
]);

$configurationmanager = new ConfigurationManager($database, $config);
$configurationmanager->populateConf();

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