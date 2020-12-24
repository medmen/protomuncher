<?php
$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$controller = new Index($request, $response);