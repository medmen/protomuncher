<?php
namespace protomuncher;
use Mlaphp\Response;

require_once(__DIR__ . '/../vendor/autoload.php');

$request = new \Mlaphp\Request($GLOBALS);
$uploader = new Uploader($request);
$result = $uploader->do_upoad();

$response = new Response(__DIR__.'/../views');
$response->setView('output.html.php');

if($result === false) {
    $response->setVars(array(
        'failure' => $uploader->get_failure()
    ));
} else {
    switch ($uploader->get_mimetype) {
        case '':
            break;
        default:
            $response->setVars(array(
                'failure' => array(0 => 'wrong mimetype for upload')
            ));
    }
}

