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
        if ($this->filetype == 'pdf') {
            $converter = new PDFConverter($this->logger, $this->config);
        }

        if ($this->filetype == 'xml') {
            $converter = new XMLConverter($this->logger, $this->config);
        }
        $converter->setmodality($this->modality);
        $converter->setinput($this->upload);
        return ($converter->convert());
    }

    // We start with parsing xml for MRT
    private function convert_xml(): array
    {
        $return_arr = array();
        $countIx = 0;
        $prot_name = '';
        $xml = new XMLReader();
        $xml->open($this->upload);
        /**
         * To use xmlReader easily we have to make sure we parse at the outermost level of repeating elements.
         * This is because xmlReaders next() option does not behave as one would think by intuition
         */

        while ($xml->read() && $xml->name != 'PrintProtocol') {
        }

        while ($xml->name == 'PrintProtocol') {
            $element = new SimpleXMLElement($xml->readInnerXML()); //

            $proto_path = explode('\\', strval($element->SubStep->ProtHeaderInfo->HeaderProtPath));

            $prod = array(
                'region' => $proto_path[3],
                'protocol' => $proto_path[4] . '-' . $proto_path[5],
                'sequence' => $proto_path[6],
                'TA' => strval($element->SubStep->ProtHeaderInfo->HeaderProperty),
            );

            //@TODO: fetch those from config
            $target_elements = array('Schichten', 'Phasenkod.-Richt.', 'FoV Auslese', 'TR', 'TE');

            foreach ($element->SubStep->Card as $card) {
                foreach ($card->ProtParameter as $seq_property) {
                    if (in_array(strval($seq_property->Label), $target_elements)) {
                        $label = strval($seq_property->Label);
                        $value = strval($seq_property->ValueAndUnit);
                        $prod[$label] = $value;
                    }
                }
            }

            $return_arr[] = $prod;            // print_r($prod);
            if ($countIx == 4) break; // cut loops for testing

            $countIx++;
            $xml->next('PrintProtocol');
            unset($element);
        }
        return ($return_arr);
    }

}