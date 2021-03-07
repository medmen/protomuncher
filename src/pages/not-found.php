<?php
declare(strict_types=1);

namespace protomuncher\pages;

use Mlaphp\Request;
use Mlaphp\Response;

$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$controller = new protomuncher\Controller\NotFoundController($request, $response);
