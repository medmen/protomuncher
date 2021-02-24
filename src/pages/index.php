<?php
declare(strict_types=1);

namespace protomuncher\pages;

use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\Controller\IndexController;

$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$controller = new IndexController($request, $response);