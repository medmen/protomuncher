<?php

declare(strict_types=1);

namespace protomuncher\classes;

use Mlaphp\Request;
use Monolog\Logger;

class Uploader
{
    private $logger, $maxfilesize, $uploadtarget, $filetype, $error_message;

    public function __construct(Request $request, Logger $logger)
    {
        $this->uploaddir = dirname(__DIR__) . '/../uploads';
        $this->modality = $request->post['geraet'];
        $this->request = $request->files['inputpdf'];
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
        $this->error_message = array();
        $this::set_maxfilesize();
    }

    private function file_upload_max_size()
    {
        static $max_size = -1;

        if ($max_size < 0) {
            // Start with post_max_size.
            $post_max_size = $this->parse_size(ini_get('post_max_size'));
            if ($post_max_size > 0) {
                $max_size = $post_max_size;
            }

            // If upload_max_size is less, then reduce. Except if upload_max_size is
            // zero, which indicates no limit.
            $upload_max = $this->parse_size(ini_get('upload_max_filesize'));
            if ($upload_max > 0 && $upload_max < $max_size) {
                $max_size = $upload_max;
            }
        }
        return $max_size;
    }

    private function parse_size($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
        $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
        if ($unit) {
            // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        } else {
            return round($size);
        }
    }

    private function is_valid_upload(): bool
    {
        if (!isset($this->request)) {
            $this->logger->error('No Upload');
            $this->error_message[] = 'No upload issued';
            return false;
        }

        if (!is_array($this->request)) {
            $this->logger->error('No Upload');
            $this->error_message[] = 'No upload issued';
            return false;
        }

        // checking for valid results makes tests happy :)
        if (!isset($this->request['error'])) {
            $this->request['error'] = 0;
        }

        if (isset($this->request['error']) and !is_int($this->request['error'])) {
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

        if (isset($this->request['size']) and $this->request['size'] > $this->maxfilesize) {
            $this->logger->error('Exceeded filesize limit.');
            $this->error_message[] = 'Exceeded filesize limit';
            return false;
        }
        return true;
    }

    private function clear_upload_dir(): bool
    {
        $res = array_map(array($this, 'delete_file'), glob($this->uploaddir . '/*')); // glob leaves hidden files alone, just as we want
        if (is_array($res)) {
            if (count($res) == 0) {
                return true;
            }
            $this->logger->notice('clear_upload_dir() returned array ' . var_export($res));
            if (in_array(false, $res)) {
                $this->error_message[] = 'Could not clear uploads directory';
                return false;
            }
        }
        // we should not get here, but lets be sure
        $this->logger->error('clear_upload_dir() did not return bool but ' . gettype($res) . ' with value ' . var_export($res));
        return false;
    }

    private function delete_file($afile)
    {
        if (unlink($afile)) {
            $this->logger->notice('Successfully deleted old upload ' . $afile);
            return true;
        } else {
            $this->logger->error('Could not delete old upload ' . $afile);
            return false;
        }
    }

    private function move_upload(): bool
    {
        // shortcut for testing purpose
        /**
        if ($this->logger['name'] === 'test') {
            return true;
        }
        **/

        // hardcoded upload destination for now.. @TODO: make destination configurable,
        if (move_uploaded_file($this->request['tmp_name'], $this->uploadtarget)) {
            $this->logger->notice('Successfull file upload for ' . $this->request['name']);
            return true;
        } else {
            $this->logger->error('Failed to move upload ' . $this->request['name'] . ' to ' . $this->uploadtarget);
            $this->error_message[] = 'Failed to move upload ' . $this->request['name'];
            return false;
        }
    }

    private function set_filetype(): string
    {
        $mimetype = strval(mime_content_type($this->request['tmp_name'])); // may return false on error, be sure to cast as string
        $this->filetype = substr(strrchr($mimetype, '/'), 1); // if mimetype holds a slash (/) return everything behind
        return ($this->filetype);
    }

    private function is_valid_filetype(): bool
    {
        $allowed_filetypes = array('pdf', 'xml');
        if (isset($this->filetype) and in_array($this->filetype, $allowed_filetypes)) {
            return true;
        }
        $this->logger->error('upload ' . $this->request['name'] . ' is not of supported file type but ' . str_replace('/', '_', $this->filetype));
        $this->error_message[] = 'upload ' . $this->request['name'] . ' is not of supported file type';
        return false;
    }

    private function set_maxfilesize(): void
    {
        // @TODO: replace by configurable max value
        $this->maxfilesize = $this->file_upload_max_size();
    }

    private function set_upload_target(): void
    {
        $this->uploadtarget = dirname(__DIR__) . '/../uploads/target.' . $this->filetype;
    }

    public function get_failure(): array
    {
        return $this->error_message;
    }

    public function do_upload(): array
    {
        if (false == $this::clear_upload_dir()) {
            return (['success' => false]);
        }

        if (false == $this::is_valid_upload()) {
            return (['success' => false]);
        }
        $this::set_filetype();

        if (false == $this::is_valid_filetype()) {
            return (['success' => false]);
        }

        $this::set_upload_target();

        // @Important: this must be called AFTER all other checks
        if (false == $this::move_upload()) {
            return (['success' => false]);
        }

        // NO ERROS, so report success
        return (array('success' => true,
            'upload' => $this->uploadtarget,
            'filetype' => $this->filetype,
            'modality' => $this->modality,
        ));
    }
}
