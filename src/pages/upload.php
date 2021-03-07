<?php
declare(strict_types=1);

namespace protomuncher\pages;

use Mlaphp\Request;
use Mlaphp\Response;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use protomuncher\classes\Uploader;
use protomuncher\Controller\UploadController;

$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$logger = new Logger('logger');
$logger->pushHandler(new StreamHandler(dirname(__DIR__) . '/../log/test_app.log', Logger::DEBUG));
$uploader = new Uploader($request, $logger);
$controller = new UploadController($request, $response, $uploader, $logger, $config);
