<?php

declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;
use Mlaphp\Response;
use Monolog\Handler\NoopHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class UploaderTest extends TestCase
{

    public function test_index()
    {
        //setup
        $url_path = '/';
        //  $request = new Request($GLOBALS);

        $response = new Response(__DIR__ . '/../src/views');
        $response->setView('index.html.php');
        $response->setVars(array(
            'url_path' => $url_path,
        ));
        $view = $response->requireView();

        $this->assertStringContainsString('<input type="file" name="inputpdf"', $view);
        $this->assertStringContainsString('action="/upload.php"', $view);
    }

    public function test_post_do_upload()
    {
        // setup
        $filename = 'MRT.xml';
        $filepath = __DIR__ . '/fixtures/' . $filename;

        $files = [
            'inputpdf' => [
                'name' => $filename,
                'type' => 'application/xml',
                'tmp_name' => $filepath,
            ],
        ];
        $request = new Request($GLOBALS);
        $request->files = $files;
        $request->post['geraet'] = 1;
        $logger = new Logger('test');
        $logger->pushHandler(new NoopHandler());
        $uploader = new classes\Uploader($request, $logger);

        //act
        $output = $uploader->do_upload();

        //assert
        $this->assertContains('<h3>Your file was successfully uploaded!</h3>', $output);
    }
}
