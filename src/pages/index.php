<?php
declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\Controller\Index;

$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$controller = new Index($request, $response);