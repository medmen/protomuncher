<?php
$countIx = 0;

$xml = new XMLReader();
$xml->open('./uploads/Oberbauch.xml');

while($xml->read() && $xml->name != 'PrintProtocol')
{
    ;
}

while($xml->name == 'PrintProtocol')
{
    $element = new SimpleXMLElement($xml->readOuterXML()); //

    $prod = array(
        'name' => strval($element->ProtHeaderInfo->HeaderProtPath),
        'TA' => strval($element->ProtHeaderInfo->HeaderProperty),
    );

    $target_elements = array('Schichten', 'Phasenkod.-Richt.', 'FoV Auslese', 'TR', 'TE');

    foreach ($element->Card as $card) {
        foreach ($card->ProtParameter as $seq_property) {
            if (in_array(strval($seq_property->Label), $target_elements)) {
                $label = strval($seq_property->Label);
                $value = strval($seq_property->ValueAndUnit);
                $prod[$label] = $value;
            }
        }
    }


    print_r($prod);
    print "\n";
    $countIx++;

    $xml->next('PrintProtocol');
    unset($element);
}

print "Number of items=$countIx\n";
print "memory_get_usage() =" . memory_get_usage()/1024 . "kb\n";
print "memory_get_usage(true) =" . memory_get_usage(true)/1024 . "kb\n";
print "memory_get_peak_usage() =" . memory_get_peak_usage()/1024 . "kb\n";
print "memory_get_peak_usage(true) =" . memory_get_peak_usage(true)/1024 . "kb\n";

print "custom memory_get_process_usage() =" . memory_get_process_usage() . "kb\n";


$xml->close();

/**
* Returns memory usage from /proc<PID>/status in bytes.
*
* @return int|bool sum of VmRSS and VmSwap in bytes. On error returns false.
*/
function memory_get_process_usage() {
    $status = file_get_contents('/proc/' . getmypid() . '/status');
    $matchArr = array();
    preg_match_all('~^(VmRSS|VmSwap):\s*([0-9]+).*$~im', $status, $matchArr);
    if(!isset($matchArr[2][0]) || !isset($matchArr[2][1])) {
        return false;
    }

    return intval($matchArr[2][0]) + intval($matchArr[2][1]);
}