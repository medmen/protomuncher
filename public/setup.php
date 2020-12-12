<?php
// get composer autoloader
$loader = require(__DIR__.'../vendor/autoload.php');

// add out own classes
$loader->add('', __DIR__.'../src/classes');
