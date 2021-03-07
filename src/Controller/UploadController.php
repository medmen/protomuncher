<?php
declare(strict_types=1);

namespace protomuncher\Controller;

use Mlaphp\Request;
use Mlaphp\Response;
use Monolog\Logger;
use protomuncher\classes\ConfigObject;
use protomuncher\classes\Converter;
use protomuncher\classes\Formatter;
use protomuncher\classes\Uploader;

// use protomuncher\ConverterDataObject;

class UploadController
{
    protected $request, $response, $uploader, $logger, $config;

    public function __construct(Request $request, Response $response, Uploader $uploader, Logger $logger, ConfigObject $config)
    {
        $this->request = $request;
        $this->response = $response;
        $this->uploader = $uploader;
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
        $this->config = $config;
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

        $converter = new Converter($result, $this->logger, $this->config);
        $raw_data_array = $converter->convert();

        $formatter = new Formatter('md', $this->logger);
        $formatted_result = $formatter->format_pretty($raw_data_array);

        $this->response->setVars(array(
            'success' => 'erfolgreich verwandelt --- magisch !!!',
            'res' => $formatted_result,
        ));

        return ($this->response);
    }
}
