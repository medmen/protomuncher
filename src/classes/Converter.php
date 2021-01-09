<?php

class Converter
{


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