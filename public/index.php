<?php
require('setup.php');

use Mlaphp\Request;
use Mlaphp\Response;

$request = new Request($GLOBALS);
$response = new Response(__DIR__.'../src/views');