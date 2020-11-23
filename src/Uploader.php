<?php
declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;

use MonologLogger;
use MonologHandlerStreamHandler;

class Uploader extends Request
{
    private $request, $logger, $maxfilesize, $uploaddir;

    public function __construct(&$globals)
    {
        parent::__construct($globals);
        $this->logger = new Logger('logger');
        $this->logger->pushHandler(new StreamHandler(DIR . '/test_app.log', Logger::DEBUG));
        // $this->logger->pushHandler(new FirePHPHandler());
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);

        $this->maxfilesize = 10000000; // @TODO: replace by configurable value
        $this->uploaddir = 'uploads'; // @TODO: replace by configurable value
    }

    public function is_valid_upload(): bool
    {
        $this->request = Request::files;
        if ($this->request['error'] or is_array($this->request['error'])) {
            $this->logger->exception('Invalid parameters.');
            return false;
        }
        switch ($this->request['error']) {
            case UPLOAD_ERR_NO_FILE:
                $this->logger->exception('No file sent.');
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->logger->exception('Exceeded filesize limit.');
                return false;
        }

        if ($this->request['size'] > $this->maxfilesize) {
            $this->logger->exception('Exceeded filesize limit.');
            return false;
        }
    }

    function clear_upload_dir(): bool
    {
        array_map('delete_file', glob("uploads/*")); // glob leaves hidden files alone, just as we want
    }

    private function delete_file($afile)
    {
        if(unlink($this->uploaddir.DIRECTORY_SEPARATOR.$afile)) {
            $this->logger->notice('Successfully deleted old upload ' . $afile);
        } else {
            $this->logger->error('Could not delete old upload '.$afile);
        }
    }

}

/**
    function upload_pdf() {
        try {

            // Check $_FILES['inputpdf']['error'] value.
            switch ($_FILES['inputpdf']['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No file sent.');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('Exceeded filesize limit.');
                default:
                    throw new RuntimeException('Unknown errors.');
            }

            // You should also check filesize here.
            if ($_FILES['inputpdf']['size'] > 10000000) {
                throw new RuntimeException('Exceeded filesize limit.');
            }

            // DO NOT TRUST $_FILES['inputpdf']['mime'] VALUE !!
            // Check MIME Type by yourself.
            /**
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
            $finfo->file($_FILES['inputpdf']['tmp_name']),
            array(
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            ),
            true
            )) {
            throw new RuntimeException('Invalid file format.');
            }
             **/
            $step1['debug'][] = 'file: '.$file['tmp_name']."\n";

            //remove old files in upload dir - this will silently fail if permissions are incorrect!
            array_map('unlink', glob("uploads/*")); // glob leaves hidden files alone, just as we want

            if(move_uploaded_file($_FILES['inputpdf']['tmp_name'], 'uploads/target.pdf'))
            {
                $step1['message'] = 'Upload erfolgreich';
                $step1['file'] = $uploaddir.'target.pdf';
                return($step1);
            } else {
                throw new RuntimeException('Unknown error on move.');
            }


        } catch (RuntimeException $e) {
            $step1['error'] = true;
            $step1['message'] = $e->getMessage();
            return($step1);
        }
    }


}