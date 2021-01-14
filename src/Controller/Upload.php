<?php
declare(strict_types=1);

namespace protomuncher\Controller;

use Mlaphp\Request;
use Mlaphp\Response;
use Monolog\Logger;
use protomuncher\classes\Uploader;
use protomuncher\Converter;
use protomuncher\Formatter;

// use protomuncher\ConverterDataObject;

class Upload
{
    protected $request, $response, $uploader, $logger;

    public function __construct(Request $request, Response $response, Uploader $uploader, Logger $logger)
    {
        $this->request = $request;
        $this->response = $response;
        $this->uploader = $uploader;
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
    }

    public function __invoke(): Response
    {
        $url_path = parse_url(
            $this->request->server['REQUEST_URI'],
            PHP_URL_PATH
        );

        $this->response->setView('output.html.php');

        $result = $this->uploader->do_upload();

        if ($result['success'] === false) {
            $this->response->setVars(array(
                'failure' => $this->uploader->get_failure()
            ));
            return $this->response;
        }

        // $result holds all necessary data (we hope) - cnvert that into a data object???
        //$converter_data = new ConverterDataObject($result);

        $converter = new Converter($result, $logger);
        $raw_data_array = $converter->convert();

        $formatter = new Formatter('md', $logger);

        $this->response->setVars(array(
            'success' => 'erfolgreich verwandelt --- magisch !!!',
            'res' => $formatter->format_pretty($raw_data_array),
        ));
    }
}
