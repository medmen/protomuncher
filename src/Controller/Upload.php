<?php
declare(strict_types=1);

namespace protomuncher\Controller;

use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\classes\Uploader;

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
        } else {
            //@TODO: better use match(PHP8) here?
            switch ($result['filetype']) {
                case 'pdf':
                    $this->response->setVars(array(
                        'filetype' => 'pdf'
                    ));
                    break;

                case 'xml':
                    $this->response->setVars(array(
                        'filetype' => 'xml'
                    ));
                    break;

                default:
                    $this->response->setVars(array(
                        'failure' => array(0 => 'wrong filetype for upload')
                    ));
            }
        }
        return $this->response;
    }
}
