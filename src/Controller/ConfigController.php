<?php

namespace protomuncher\Controller;

use Medoo\Medoo;
use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\classes\ConfigurationManager;

class ConfigController
{
    protected $database, $request, $response, $configuration;

    public function __construct(Medoo $database, Request $request, Response $response, ConfigurationManager $configurationmanager)
    {
        $this->database = $database;
        $this->request = $request;
        $this->response = $response;
        $this->configurationmanager = $configurationmanager;
    }

    public function __invoke(): Response
    {
        $url_path = parse_url(
            $this->request->server['REQUEST_URI'],
            PHP_URL_PATH
        );

        // all child objects of request are arrays, so we can use count to see if they are populated
        if (count($this->request->post) > 0) {
            $result = $this->configurationmanager->form2conf($this->request->post);
            $status = $result['status'];
            $message = implode(PHP_EOL, $result['message']);
        }

        $configform = $this->configurationmanager->conf2form();

        $this->response->setView('config.html.php');
        $this->response->setVars(array(
            'url_path' => $url_path,
            'status' => $status,
            'message' => $message,
            'configform' => $configform,
        ));

        return $this->response;
    }
}