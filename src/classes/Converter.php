<?php
declare(strict_types=1);

use Monolog\Logger;
use TonchikTm\PdfToHtml\Pdf;

class Converter
{
    function __construct($result_arr, Logger $logger)
    {
        $this->filetype = $result_arr['filetype'] ?? false;
        $this->upload = $result_arr['upload'] ?? false;
        $this->modality = $result_arr['modality'] ?? false;
        $this->logger = $logger;
        $this->logger->notice('Logger is now Ready in class ' . __CLASS__);

        if (!$this->filetype or !$this->upload or !$this->modality) {
            $this->logger->error('Converter initialized incomplete:' . var_export($result_arr));
            throw new Exception('Converter initialization incomplete:');
        }
    }

    public function convert()
    {
        if ($this->filetype == 'pdf') {
            $res = $this->convert_pdf();
        }

        if ($this->filetype == 'xml') {
            $res = $this->convert_xml();
        }
    }

    private function convert_pdf(): array
    {
        $this->html = $this->upload . '.html';
        $pdf = new Pdf($this->upload, ['pdftohtml_path' => '/usr/bin/pdftohtml -c', 'pdfinfo_path' => '/usr/bin/pdfinfo']);
        if (false === file_put_contents($this->html, $pdf->getHtml()->getAllPages())) {
            $this->logger->error('failed converting pdf to html');
            return array('success' => false, 'message' => 'failed converting pdf to html');
        }

        $result_arr = new HtmlToArray($this->html);
    }

}
/**
 * switch ($result['filetype']) {
 * case 'pdf':
 * $this->response->setVars(array(
 * 'filetype' => 'pdf'
 * ));
 *
 * $pdf = new Pdf(dirname(__DIR__).'../uploads/target.pdf', [
 * 'pdftohtml_path' => '/usr/bin/pdftohtml -c',
 * 'pdfinfo_path' => '/usr/bin/pdfinfo'
 * ]);
 *
 * // get content from all pages and loop for they
 * $convert_success = file_put_contents(dirname(__DIR__).'../uploads/target.html', $pdf->getHtml()->getAllPages());
 * if ($convert_success == false) {
 * $this->response->setVars(array(
 * 'failure' => 'Umwandlung PDF -> HTML fehlgeschlagen - Abbruch'
 * ));
 * return $this->response;
 * }
 *
 * $dom = HtmlDomParser::file_get_html(dirname(__DIR__).'../uploads/target.html');
 * $this->response->setVars(array(
 * 'success' => 'HTML wird verwandelt --- magisch !!!'
 * ));
 * return $this->response;
 *
 * break;
 *
 * case 'xml':
 * $this->response->setVars(array(
 * 'filetype' => 'xml'
 * ));
 * break;
 *
 * default:
 * $this->response->setVars(array(
 * 'failure' => array(0 => 'wrong filetype for upload')
 * ));
 * }
 */