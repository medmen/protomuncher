<?php
namespace protomuncher;

class mrixmlparser
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

    public function format_pretty($field_arr)
    {
        if (empty($field_arr)) {
            return false;
        }
        if (is_array($field_arr) and count($field_arr) < 4) {
            return false;
        }
        if (!$old_protocol) {
            $old_protocol = '';
        }
        if (!$old_region) {
            $old_region = '';
        }

        $pretty = '';
        $valid_formats = array(
            'md', // markdown
            'html' //
        );

        if (!in_array($format, $valid_formats)) {
            throw new \http\Exception\InvalidArgumentException('invalid format: ' . $format);
        }

        $headers_arr = array_keys($data);

        switch ($format) {
            case 'html':
                if ($data['region'] !== $old_region) {
                    $pretty .= '<h1>' . $data['region'] . '</h1>';
                    $old_region = $data['region'];
                    $old_protocol = '';
                }

                if ($data['protocol'] !== $old_protocol) {
                    $pretty .= '</table>' . PHP_EOL;
                    $pretty .= '<h2>' . $data['protocol'] . '</h2>' . PHP_EOL;
                    $pretty .= '<table>.PHP_EOL<thead>.PHP_EOL<tr><th>' . implode('</th><th>', $headers_arr) . '</th></tr>.PHP_EOL</thead>.PHP_EOL<tfoot>a nice footer</tfoot>' . PHP_EOL;
                }
                $pretty .= '<tr><td>' . implode('</td>' . PHP_EOL . '<td>', $data) . '</td></tr>' . PHP_EOL;
                break;
            case 'md':
            default:
                if ($data['region'] !== $old_region) {
                    $pretty .= '====== ' . $data['region'] . ' ======' . PHP_EOL;
                    $old_region = $data['region'];
                    $old_protocol = '';
                }

                if ($data['protocol'] !== $old_protocol) {
                    $pretty .= '===== ' . $data['protocol'] . ' =====' . PHP_EOL;
                    $pretty .= '^ ' . implode(' ^ ', $headers_arr) . ' ^' . PHP_EOL;
                }
                $pretty .= '|' . implode(' | ', $data) . ' |' . PHP_EOL;
                break;
        }
        return ($pretty);

    }

}

$pa = new MRIXmlParser();
$res = $pa->extract_data();
print_r($res);