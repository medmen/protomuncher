<?php
declare(strict_types=1);

namespace protomuncher;

class Mrixmlparser
{
    private $countIx, $target_elements, $file, $outer_tag, $field_arr;

    function __construct()
    {
        $this->target_elements = array('Schichten', 'Phasenkod.-Richt.', 'FoV Auslese', 'TR', 'TE');
        $this->countIx = 0; // count lines
        $this->outer_tag = 'PrintProtocol';
        $this->field_arr = array();
        $this->format = 'md'; // parse to markdown by default
    }


    public function extract_data($file = './uploads/USER_protokolle.xml')
    {
        $this->file = $file;
        $xml = new \XMLReader();
        $xml->open($this->file);

        /**
         * To use xmlReader easily we have to make sure we parse
         * at the outermost level of repeating elements.
         */

        while ($xml->read() && $xml->name != $this->outer_tag) {
            ;
        }

        while ($xml->name == $this->outer_tag) {
            $element = new \SimpleXMLElement($xml->readInnerXML()); //

            $proto_path = explode('\\', strval($element->SubStep->ProtHeaderInfo->HeaderProtPath));

            $this->field_arr[$this->countIx] = array(
                'region' => $proto_path[3],
                'protocol' => $proto_path[4] . '-' . $proto_path[5],
                'sequence' => $proto_path[6],
                'TA' => strval($element->SubStep->ProtHeaderInfo->HeaderProperty),
            );

            foreach ($element->SubStep->Card as $card) {
                foreach ($card->ProtParameter as $seq_property) {
                    if (in_array(strval($seq_property->Label), $this->target_elements)) {
                        $label = strval($seq_property->Label);
                        $value = strval($seq_property->ValueAndUnit);
                        $this->field_arr[$this->countIx][$label] = $value;
                    }
                }
            }

            $this->countIx++;

            $xml->next('PrintProtocol');
            unset($element);
        }

        $xml->close();

        return ($this->field_arr);
    }
}

$pa = new MRIXmlParser();
$res = $pa->extract_data();
print_r($res);