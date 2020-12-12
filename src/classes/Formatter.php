<?php

namespace protomuncher;

class Formatter
{
    private $format, $old_protocol, $old_region, $pretty;

    function __construct($format)
    {
        $valid_formats = array(
            'md', // markdown
            'html' //
        );

        if (!in_array($format, $valid_formats)) {
            throw new \http\Exception\InvalidArgumentException('invalid format: ' . $format);
        }
        $this->format = $format;
    }

    public function format_pretty($field_arr)
    {
        try {
            if (empty($field_arr)) {
                throw new \ErrorException('missing argument');
            }
            if (is_array($field_arr) and count($field_arr) < 4) {
                throw new \ErrorException('missing argument');
            }
        } catch (\ErrorException $e) {
            return array('success' => false,
                        'message' => $e->getMessage()
                );
        };


        if (!$this->old_protocol) {
            $this->old_protocol = '';
        }
        if (!$this->old_region) {
            $this->old_region = '';
        }

        $this->pretty = '';

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