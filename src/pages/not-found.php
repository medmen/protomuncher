<?php

use Mlaphp\Request;
use Mlaphp\Response;

$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$controller = new protomuncher\Controller\NotFound($request, $response);
