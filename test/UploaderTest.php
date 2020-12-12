<?php
declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;
use PHPUnit\Framework\TestCase;

class UploaderTest extends TestCase
{
    public function test_index()
    {
        $request = new \Mlaphp\Request($GLOBALS);
        $output = $this->request('GET', 'index.html');
        $this->assertContains('<input type="file" name="upload"', $output);
    }

    public function test_post_do_upload()
    {
        $filename = 'ci-phpuni-test-downloads-777.png';
        $filepath = APPPATH.'tests/fixtures/'.$filename;

        $files = [
            'userfile' => [
                'name'     => $filename,
                'type'     => 'image/png',
                'tmp_name' => $filepath,
            ],
        ];
        $this->request->setFiles($files);
        $output = $this->request('POST', 'upload/do_upload');
        $this->assertContains('<h3>Your file was successfully uploaded!</h3>', $output);
    }
}
