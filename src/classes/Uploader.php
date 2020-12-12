<?php
declare(strict_types=1);

namespace protomuncher;

use Mlaphp\Request;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Uploader
{
    private $logger, $maxfilesize, $uploaddir, $mimetype, $error_message;

    public function __construct(Request $request)
    {
        $this->request = $request->files['inputpdf'];
        $this->logger = new Logger('logger');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/test_app.log', Logger::DEBUG));
        // $this->logger->pushHandler(new FirePHPHandler());
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);

        $this->maxfilesize = 10000000; // @TODO: replace by configurable value
        $this->uploaddir = 'uploads'; // @TODO: replace by configurable value
        $this->error_message = array();
    }

    private function is_valid_upload(): bool
    {
        if (!is_array($this->request)){
            $this->logger->error('No Upload');
            $this->error_message[] = 'No upload issued';
            return false;
        }

        if ($this->request['error'] or is_array($this->request['error'])) {
            $this->logger->error('Invalid parameters');
            $this->error_message[] = 'Invalid parameters';
            return false;
        }
        switch ($this->request['error']) {
            case UPLOAD_ERR_NO_FILE:
                $this->logger->error('No file sent');
                $this->error_message[] = 'No file sent';
                return false;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->logger->error('Exceeded filesize limit');
                $this->error_message[] = 'Exceeded filesize limit';
                return false;
        }

        if ($this->request['size'] > $this->maxfilesize) {
            $this->logger->error('Exceeded filesize limit.');
            $this->error_message[] = 'Exceeded filesize limit';
            return false;
        }
        return true;
    }

    private function clear_upload_dir(): bool
    {
        $res = array_map(array($this, 'delete_file'), glob("uploads/*")); // glob leaves hidden files alone, just as we want
        if(is_array($res)){
            if(count($res) == 0) {
                return true;
            }
            $this->logger->notice('clear_upload_dir() returned array '.var_export($res));
            if(in_array(false, $res)) {
                $this->error_message[] = 'Could not clear uploads directory';
                return false;
            };
        }
        // we should not get here, but lets be sure
        $this->logger->error('clear_upload_dir() did not return bool but '.gettype($res).' with value '.var_export($res));
        return false;
    }

    private function delete_file($afile)
    {
        if(unlink($this->uploaddir.DIRECTORY_SEPARATOR.$afile)) {
            $this->logger->notice('Successfully deleted old upload ' . $afile);
            return true;
        } else {
            $this->logger->error('Could not delete old upload '.$afile);
            return false;
        }
    }

    private function move_upload()
    {
        // hardcoded upload destination for now.. @TODO: make destination configurable
        $destination = __DIR__ . '/../uploads/target.pdf';
        if(move_uploaded_file( $this->request['tmp_name'], $destination))
        {
            $this->logger->notice('Successfull file upload for '. $this->request['name']);
            return true;
        } else {
            $this->logger->error('Failed to move upload '.$this->request['name'].' to uploads/target.pdf');
            $this->error_message[] = 'Failed to move upload '.$this->request['name'].' to uploads/target.pdf';
            return false;
        }

    }

    private function get_mimetype()
    {
        $this->mimetype = mime_content_type($this->request['tmp_name']);
        switch($this->mimetype) {
            case 'application/pdf':
                return('pdf');
            break;
            case 'application/xml':
                return('xml');
            default:
                $this->logger->error('upload '.$this->request['name'].' is not of supported file type but '.$this->mimetype);
                $this->error_message[] = 'upload '.$this->request['name'].' is not of supported file type';
                return false;
        }
    }

    public function get_failure(){
        return $this->error_message;
    }

    public function get_mimetype() {
        return $this->mimetype;
    }


    public function do_upoad()
    {
        if(false == $this::clear_upload_dir()) {
            return(false);
        }

        if(false == $this::is_valid_upload()) {
            return(false);        }

        if(false == $this::move_upload()){
            return(false);
        }

        $this->mimetype = $this::get_mimetype();
        if(false == $this->mimetype){
            return(false);
        }

        // NO ERROS, so report success
        // $this->error_message[] = 'Upload Successfull';
        $this->filetype = $this->mimetype;
        return(true);
    }
}
