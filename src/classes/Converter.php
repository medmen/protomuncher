<?php

declare(strict_types=1);

namespace protomuncher\classes;

use Exception;
use Monolog\Logger;
use SimpleXMLElement;
use XMLReader;

class Converter
{
    function __construct($result_arr, Logger $logger, ConfigObject $config)
    {
        $this->filetype = $result_arr['filetype'] ?? false;
        $this->upload = $result_arr['upload'] ?? false;
        $this->modality = $result_arr['modality'] ?? false;
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
        $this->config = $config;

        if (!$this->filetype or !$this->upload or !$this->modality) {
            $this->logger->error('Converter initialized incomplete:' . var_export($result_arr));
            throw new Exception('Converter initialization incomplete:');
        }
    }

    public function convert(): array
    {
        switch (true) {
            case ($this->filetype == 'pdf' and $this->modality == "1"):
                $converter = new MrtPdfConverter($this->logger, $this->config);
                break;
            case ($this->filetype == 'pdf' and $this->modality == "2"):
                $converter = new CtPdfConverter($this->logger, $this->config);
                break;
            case ($this->filetype == 'xml' and $this->modality == "1"):
                $converter = new MrtXmlConverter($this->logger, $this->config);
                break;
            case ($this->filetype == 'xml' and $this->modality == "2"):
                $converter = new CtXmlConverter($this->logger, $this->config);
                break;
            default:
                return(array('success' => false, 'message' => 'no matching converter found'));
        }
        $converter->setinput($this->upload);
        return ($converter->convert());
    }
}
