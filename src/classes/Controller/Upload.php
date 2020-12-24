<?php
declare(strict_types=1);

namespace protomuncher\Controller;

use Mlaphp\Request;
use Mlaphp\Response;
use protomuncher\Uploader;

class Upload
{
    protected $request, $response, $uploader;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->uploader = new Uploader($this->request);
    }

    public function __invoke()
    {
        $url_path = parse_url(
            $this->request->server['REQUEST_URI'],
            PHP_URL_PATH
        );

        $result = $this->uploader->do_upoad();
        if ($result === false) {
            $this->response->setVars(array(
                'failure' => $this->uploader->get_failure()
            ));
        } else {
            //@TODO: better use match(PHP8) here?
            switch ($this->uploader->get_mimetype()) {
                case 'pdf':
                    break;

                case 'xml':
                    break;

                default:
                    $this->response->setVars(array(
                        'failure' => array(0 => 'wrong mimetype for upload')
                    ));
            }
        }
    }
}
