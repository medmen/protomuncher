<?php
declare(strict_types=1);

namespace protomuncher\Controller;

use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\classes\Uploader;
use protomuncher\Formatter;

class Upload
{
    protected $request, $response, $uploader;

    public function __construct(Request $request, Response $response, Uploader $uploader)
    {
        $this->request = $request;
        $this->response = $response;
        $this->uploader = $uploader;
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

        $converter = new Converter($result);
        $raw_data_array = $converter->convert();

        $formatter = new Formatter('md');

        $this->response->setVars(array(
            'success' => 'erfolgreich verwandelt --- magisch !!!',
            'res' => $formatter->format_pretty($raw_data_array),
        ));

    }
}
