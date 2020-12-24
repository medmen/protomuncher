<?php
declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;
use Mlaphp\Response;
use PHPUnit\Framework\TestCase;

class UploaderTest extends TestCase
{
    public function test_index()
    {
        $request = new Request($GLOBALS);

        $response = new Response(__DIR__ . '/../src/views');
        $response->setView('upload.html.php');
        $response->setVars(array(
            'request' => $request
        ));
        $view = $response->requireView();

        $this->assertStringContainsString('<input type="file" name="inputpdf"', $view);
    }

    public function test_post_do_upload()
    {
        $filename = 'MRT.xml';
        $filepath = __DIR__ . '/fixtures/' . $filename;

        $files = [
            'userfile' => [
                'name' => $filename,
                'type' => 'application/xml',
                'tmp_name' => $filepath,
            ],
        ];
        $this->request = new Request($GLOBALS);
        $this->request->files = $files;
        $uploader = new Uploader($this->request);
        $output = $uploader->do_upoad();
        $this->assertContains('<h3>Your file was successfully uploaded!</h3>', $output);
    }
}
