<?php

namespace protomuncher\Controller;

use Mlaphp\Request;
use Mlaphp\Response;

class Index
{
    protected $request, $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    public function __invoke()
    {
        $url_path = parse_url(
            $this->request->server['REQUEST_URI'],
            PHP_URL_PATH
        );

        $this->response->setView('index.html.php');
        $this->response->setVars(array(
            'url_path' => $url_path,
        ));

        return $this->response;
    }
}
