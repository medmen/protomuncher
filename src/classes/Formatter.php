<?php

declare(strict_types=1);

namespace protomuncher\classes;

use ErrorException;
use http\Exception\InvalidArgumentException;
use Monolog\Logger;

class Formatter
{
    private $format, $old_protocol, $old_region, $pretty;

    function __construct($format, Logger $logger)
    {
        $valid_formats = array(
            'md', // markdown
            'html' //
        );

        if (!in_array($format, $valid_formats)) {
            throw new InvalidArgumentException('invalid format: ' . $format);
        }
        $this->format = $format;
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);
    }

    public function format_pretty($data): array
    {
        $data = array_values($data);
        $pretty = '';

        if (!$this->old_protocol) {
            $this->old_protocol = '';
        }

        if (!$this->old_region) {
            $this->old_region = '';
        }

        $this->pretty = '';

        $headers_arr = array_keys($data[0]);
        // remove region an protocol from headers
        $headers_arr = array_filter(
            $headers_arr,
            fn ($val) => !in_array($val, array('region', 'protocol'))
        );

        foreach ($data as $row) {
            switch ($this->format) {
                case 'html':
                    if ($row['region'] !== $this->old_region) {
                        $pretty .= '<h1>' . $row['region'] . '</h1>';
                        $this->old_region = $row['region'];
                        $this->old_protocol = '';
                    }

                    if ($row['protocol'] !== $this->old_protocol) {
                        $pretty .= '</table>' . PHP_EOL;
                        $pretty .= '<h2>' . $row['protocol'] . '</h2>' . PHP_EOL;
                        $pretty .= '<table>.PHP_EOL<thead>.PHP_EOL<tr><th>' . implode('</th><th>', $headers_arr) . '</th></tr>.PHP_EOL</thead>.PHP_EOL<tfoot>a nice footer</tfoot>' . PHP_EOL;
                        $this->old_protocol = $row['protocol'];
                    }
                    unset($row['region'], $row['protocol']);
                    $pretty .= '<tr><td>' . implode('</td>' . PHP_EOL . '<td>', $row) . '</td></tr>' . PHP_EOL;
                    break;
                case 'md':
                default:
                    if ($row['region'] !== $this->old_region) {
                        $pretty .= '====== ' . $row['region'] . ' ======' . PHP_EOL;
                        $this->old_region = $row['region'];
                        $this->old_protocol = '';
                    }

                    if ($row['protocol'] !== $this->old_protocol) {
                        $pretty .= '===== ' . $row['protocol'] . ' =====' . PHP_EOL;
                        $pretty .= '^ ' . implode(' ^ ', $headers_arr) . ' ^' . PHP_EOL;
                        $this->old_protocol = $row['protocol'];
                    }
                    unset($row['region'], $row['protocol']);
                    $pretty .= '|' . implode(' | ', $row) . ' |' . PHP_EOL;
                    break;
            }
        }
        return array('success' => true,
            'message' => 'Formatierung der Rohdaten als ' . $this->format . ' erfolgreich',
            'data' => $pretty
        );
    }
}
