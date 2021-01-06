<?php
declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\classes\Uploader;
use protomuncher\Controller\Upload;

$request = new Request($GLOBALS);
$response = new Response(dirname(__DIR__) . '/views');
$uploader = new Uploader($request);
$controller = new Upload($request, $response, $uploader);
