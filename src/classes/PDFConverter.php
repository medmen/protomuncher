<?php
declare(strict_types=1);

namespace protomuncher\classes;


use KubAT\PhpSimple\HtmlDomParser;
use Monolog\Logger;
use TonchikTm\PdfToHtml\Pdf;

class PDFConverter implements IConverter
{
    private $modality, $input, $logger, $config;

    //@TODO: add COnfig to constructor so we can change paths
    function __construct(Logger $logger, ConfigObject $config)
    {
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
        $this->config = $config;
    }

    function setmodality(string $modality): void
    {
        $this->modality = $modality;
    }

    function setinput(string $input): void
    {
        $this->input = $input;
    }

    function get_limits($limits, $max): array
    {
        // see if limits holds a range
        if (stristr($limits, '-')) {
            [$start, $end] = explode('-', $limits);
            $start = intval(trim($start));
            $end = intval(trim($end));

            // sanity checks
            if ($start < 0) {
                $start = $start * -1;
            }

            if ($end < 0) {
                $end = $end * -1;
            }

            if ($end > $max) {
                $end = $max;
            }

            if ($start > $end) {
                // switch numbers
                $new_end = $start;
                $start = $end;
                $end = $new_end;
            }

            return (range($start, $end));
        }

        if (stristr($limits, ',')) {
            $items = array_map(
                function ($value) {
                    return intval(trim($value)); // trim each value and turn into int
                }, explode(',', $limits));
            foreach ($items as $item) {
                if ($item > $max) {
                    unset($item);
                }
            }
            return (array_unique($items)); // remove duplicate values
        }

        // assume its a single number
        $limit = intval(trim($limits));
        if ($limit > $max) {
            $limit = $max;
        }
        return (array($limit));
    }

    function convert(): array
    {
        $this->html = $this->input . '.html';
        $pdf = new Pdf($this->input, [
            'pdftohtml_path' => '/usr/bin/pdftohtml -c',
            'pdfinfo_path' => '/usr/bin/pdfinfo']);

        $pdfInfo = $pdf->getInfo();
        $countPages = $pdf->countPages();

        $pages = $this->get_limits($this->config['helpers']['limit_files'], $countPages);

        /**
         * if (false === file_put_contents($this->html, $pdf->getHtml()->getAllPages())) {
         * $this->logger->error('failed converting pdf to html');
         * return array('success' => false, 'message' => 'failed converting pdf to html');
         * }
         */

        if ($this->modality == 'MRT') {
            foreach ($limits as $limit) {
                $return[] = $this->convert_for_MRT($pdf->getHtml()->getPage($limit));
            }

        }

        return ($return);
    }

    function convert_for_MRT($html): array
    {
        $dom = HtmlDomParser::str_get_html($html);
        $dom->find('img')->outertext = '';// Strip out all images, if any
        $dom->find('div p.ft00')->outertext = '';// Strip out MRI-Machine Name

        /************** GTH ******************/
        foreach ($dom->find('div p.ft05') as $element) // Strip out Comments
        {
            $converted = 0;
            // Special: poppler puts some wanted values in p.ft05 element, catch those
            foreach ($conf['validentries'] as $wanted) {
                if (preg_match('#\b' . preg_quote($wanted, '#') . '\b#i', $element->innertext)) {
                    if ($conf['debug']) {
                        trigger_error("DEBUG: cought bogus ft5 element $wanted in $element->innertext", E_USER_NOTICE);
                    }
                    //cought a target element, turn into p.ft03 element with altered name
                    $element->class = 'ft03';
                    $element->innertext = $wanted;
                    $converted = 1;
                    break;
                }
            }

            if (!$converted) {
                $element->outertext = '';
                if ($conf['debug']) {
                    trigger_error("Stripped 1 Comment..<br>\n", E_USER_NOTICE);
                }
            }
        }

        foreach ($dom->find('div p.ft01') as $protocol_full) {
            if ($conf['debug']) {
                trigger_error("parsing 1 protocol..<br>\n", E_USER_NOTICE);
            }
            // extract the region/protocol/sequence
            $rps = $protocol_full->innertext;
            $protocol_elements = explode('\\', $rps);
            if (count($protocol_elements) > 5) {
                $sequence = $protocol_elements[6];
                $protocol = $protocol_elements[4] . '_' . $protocol_elements[5];
                $region = $protocol_elements[3];
                $region_proto_sequence = $region . '_' . $protocol . '_' . $sequence;

                $output_array[$region_proto_sequence]['region'] = $region;
                $output_array[$region_proto_sequence]['protocol'] = $protocol;
                $output_array[$region_proto_sequence]['sequence'] = strtoupper(str_replace('_', ' ', $sequence));

                // explode the sequence-name, it usually holds hints for measurment direction
                // TODO: find a more adequate way to extract that info
                $seq_parts = explode('_', $sequence);
                foreach ($seq_parts as $part) {
                    if (in_array(strtolower(trim($part)), array('tra', 'sag', 'cor'))) {
                        $output_array[$region_proto_sequence]['direction'] = strtolower(trim($part));
                        break;
                    }
                }
            }
        }

        foreach ($dom->find('p.ft02') as $arrival_time) {
            if ($conf['debug']) {
                trigger_error("extracting measurement time from $arrival_time->innertext ..<br>\n", E_USER_NOTICE);
            }
            // innertext holds multiple strings in "name: value" format, separated by multiple blank spaces
            // if we split by 1 or more blank spaces, first item is 'TA', second item holds time value
            $parts = preg_split("/\s+/", $arrival_time->innertext);
            if ('TA:' == trim($parts[0])) {
                $output_array[$region_proto_sequence]['messdauer'] = trim($parts[1]);
                if ($conf['debug']) {
                    trigger_error(" measurement time is .." . trim($parts[1]) . "<br>\n", E_USER_NOTICE);
                }
            }
            break; // ne need to search for other occurrences
        }

        foreach ($dom->find('p.ft03') as $potential_hit) {
            $unvalidated_entry = trim(str_replace('&#160;', '', strtolower($potential_hit->innertext)));
            $unvalidated_entry = str_replace('.', ',', $unvalidated_entry); // german decimal separator
            if ($conf['debug']) {
                trigger_error("DEBUG: checkin if $unvalidated_entry is in valid entries ...<br>\n", E_USER_NOTICE);
            }

            if (in_array($unvalidated_entry, $conf['validentries'])) {
                $actual_hit = $unvalidated_entry;
                $hit = 1;
                continue;
            }

            if (1 == $hit) {
                if ($conf['stripunits']) {
                    $unvalidated_entry = strtok($unvalidated_entry, " ");
                }
                $output_array[$region_proto_sequence][$actual_hit] = $unvalidated_entry;
                if ($conf['debug']) {
                    trigger_error("DEBUG: $actual_hit is a hit containing $unvalidated_entry !<br>\n", E_USER_NOTICE);
                }
                $hit = 0;
                continue;
            }
        }

        $dom->clear();
        return ($output_array);
    }

}