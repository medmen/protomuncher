<?php
/**
 * @package ProtoMuncher
 * @author Alexander "galak" Schuster
 * @copyright Copyright &copy; 2008, galak
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @abstract this is a none-sophisticated, quickly-hacked script to transform Siemens-MRI-Protocols into some more "readeable" form
 * @uses html_dom_parser.php http://simplehtmldom.sourceforge.net/ see manual there if you wish to change the source of this script
 * @requires PHP > 5.2 for boolean parsing using ext/filter, PHP 5.1 will do if you install ext/filter (e.g. via PECL) 
 * @requires pdftohtml, a Linux commandline-tool based on XPDF
 * @abstract run pdftohtml -c -i input.pdf output.html, this will produce the html-files with a 
 * "complex layout" this script can parse, images should get filtered out.
 * other pdf-to-html-converters (i.e. for windows) should basically do the same thing, but the layout of the 
 * output-html will probably differ so you would need to adapt the sourcecode in this script.
 * @limitations 
 * - so far the script is "stupid" in concern to fields using the same variable several times within the
 * same dataset (i.e. Phasenkodierrichtung is assigned 3 times for topogram-sequence)
 * As this is annoying but not painful, i will stick to just taking the first field and silently discarding all other entries for a start!
 * - $conf_write_big_file has no effect yet, the script will always output everything into 1 file
 *
 *
 * @TODO: generate output to file, not screen
 * @TODO: write web surface
 * @TODO: autocall pdftohtml
 * @TODO: automatic cleanup after conversion
 * @TODO: detect numer of files automagically when conf_limit_files is left empty
  **/

function get_defaultconf() {
	// you can limit the files to parse by entering their numbers here
	// files should be consecutive, otherwise you will only get Garbage out of this
	// example: $limit_files = "1,4"; would parse file Abdomen-1.html, Abdomen-2.html ... till 4
	// leave empty if you wish to parse 1 file only!
	$defaultconf['limitfiles'] = '';
	
	// define nametags for valid entries
	// see HTML-source of the Protocol-files for possible entries
	// definition: MUST be lowercase!
	$defaultconf['validentries'] = array(
		'schichtdicke',
		'distanzfaktor',
		'schichten',
		'tr',
		'te',
		'fov auslese',
		'basis-auflösung',
		'messdauer'
	);
	// setting this to 0, false or no 
	// will cause seperate HTML-File-Output for each protocol
	// default is on which will write everything into 1 big html file 
	// @Todo: implement writing to several files for v1.0
	//$conf_write_big_file = 1;
	
	// developer-setting, enable debugging-messages
	// set to 0, no or false on production site!
	$defaultconf['debug'] = false;
	
	// strip units?
	$defaultconf['stripunits'] = true;
	
	return($defaultconf);
}
/**
// If there is data given py POST, overwrite the predefined config settings
foreach ($_POST as $name => $value) {
	$$name = $value;
	var_export($$name, true);
}
die('debug');
**/
//################### config ends ####################
// define some globals

function prepare_parsing($conf = false) {
	global $region, $protocol, $sequence, $new_file;
	
	if(false == $conf) {
		$conf = get_defaultconf();
	}
	
	// set some starter variables
	$file_array = array();
	$count = 0;
	
	$region = '';
	$protocol = '';
	$sequence = '';
	$new_file = '';
	
	// see if we need to limit files
	if(empty($conf['limitfiles']))
	{
		$file_array = glob("uploads/target-*.html");
	}
	else
	{
		list($start,$end) = explode(',',$conf['limitfiles']);
		//quick check for correctness
		if($start < 1 or $end < $start)
		{
			trigger_error("LIMIT FILES ist gesetzt, aber fehlerhaft - Abbruch..<br>\n", E_USER_ERROR);
		} 	
		
		// build filenames
		for ($i = $start; $i <= $end; $i++)
		{
			// we only need odd filenumbers
			if($i % 2 == 0)
			{
				continue;
			}
			//$file_complete_name = $conf_base_filename.$i.'.'.$conf_base_fileextension;
			$file_complete_name = 'uploads/target-'.$i.'.html';
			if(file_exists($file_complete_name)) {
				unset($string);
				$file_array[] = $file_complete_name;
			} else {
				trigger_error("$file_complete_name wurde nicht gefunden<br>\n", E_USER_NOTICE);
			}
		}
	}
	return($file_array);
}

function parse_data($file, $conf = false)
{
	global $region_proto_sequence, $output_array;

	if(false == $conf) {
		$conf = get_defaultconf();
	}

	if(!is_array($output_array)) {
			$output_array = array();
	}
	
	$hit_count = 0;
	$actual_hit = '';
	$hit = 0;

	// include the html-dom-parser, it does lots of the magic :) 
	//include_once('html_dom_parser.php');
	require_once('simple_html_dom.php');

	$dom = file_get_html($file);

	if(!is_object($dom)) {
		trigger_error("$file enthält kein DOM - Abbruch..<br>\n", E_USER_ERROR);
	}

	// Strip out all images, if any
	$dom->find('img')->outertext = '';
	// Strip out MRI-Machine Name
	$dom->find('div p.ft00')->outertext = '';

	// Strip out Comments
	foreach($dom->find('div p.ft05') as $element)
	{
		$converted = 0;
		// Special: poppler puts some wanted values in p.ft05 element, catch those
		foreach($conf['validentries'] as $wanted) {
			// if(stristr($element->innertext, $wanted)) {
				if(preg_match('#\b'.preg_quote($wanted, '#') . '\b#i', $element->innertext)){
					if($conf['debug']) {
						trigger_error("DEBUG: cought bogus ft5 element $wanted in $element->innertext", E_USER_NOTICE);
					}
				//cought a target element, turn into p.ft03 element with altered name
				$element->class='ft03';
				$element->innertext=$wanted;

				$converted = 1;
				break;
				// continue 2; // go on with next outer iteration
			}
		}

		if(!$converted) {
			$element->outertext = '';
			if($conf['debug']) {
				trigger_error("Stripped 1 Comment..<br>\n", E_USER_NOTICE);
			}
		}
	}

	foreach($dom->find('div p.ft01') as $protocol_full)
	{
		if($conf['debug']) {
			trigger_error("parsing 1 protocol..<br>\n", E_USER_NOTICE);
		}

		// extract the region/protocol/sequence
		$rps = $protocol_full->innertext;
		$protocol_elements = explode('\\',$rps);
		if(count($protocol_elements) > 5) {
			$sequence = $protocol_elements[6];
			$protocol = $protocol_elements[4].'_'.$protocol_elements[5];
			$region	= $protocol_elements[3];
			$region_proto_sequence = $region.'_'.$protocol.'_'.$sequence; 
				
			$output_array[$region_proto_sequence]['region'] = $region;
			$output_array[$region_proto_sequence]['protocol'] = $protocol;
			$output_array[$region_proto_sequence]['sequence'] = strtoupper(str_replace('_', ' ', $sequence));
			
			// explode the sequence-name, it usually holds hints for measurment direction
			// TODO: find a more adequate way to extract that info
			$seq_parts = explode('_', $sequence); 
			foreach($seq_parts as $part) {
				if(in_array(strtolower(trim($part)), array('tra','sag','cor'))) {
					$output_array[$region_proto_sequence]['direction'] = strtolower(trim($part));
					break;
				}
			}
		} 
	}

	foreach($dom->find('p.ft02') as $arrival_time)
	{
		if($conf['debug']) {
			trigger_error("extracting measurement time from $arrival_time->innertext ..<br>\n", E_USER_NOTICE);
		}

		// innertext holds multiple strings in "name: value" format, separated by multiple blank spaces
		// if we split by 1 or more blank spaces, first item is 'TA', second item holds time value 
		$parts = preg_split("/\s+/", $arrival_time->innertext);
		
		if('TA:' == trim($parts[0])) {
			$output_array[$region_proto_sequence]['messdauer'] = trim($parts[1]);
			if($conf['debug']) {
				trigger_error(" measurement time is ..".trim($parts[1])."<br>\n", E_USER_NOTICE);
			}			
		}	
		break; // ne need to search for other occurrences
	}

	foreach($dom->find('p.ft03') as $potential_hit)	{
		$unvalidated_entry = trim(str_replace('&#160;','',strtolower($potential_hit->innertext)));
		$unvalidated_entry = str_replace('.', ',', $unvalidated_entry); // german decimal separator
		if($conf['debug']) {	
			trigger_error("DEBUG: checkin if $unvalidated_entry is in valid entries ...<br>\n", E_USER_NOTICE);
		}
		
		if(in_array($unvalidated_entry, $conf['validentries'])) {
			$actual_hit = $unvalidated_entry;
			$hit = 1;
			continue;
		}
		
		if(1 == $hit) {
			if($conf['stripunits']) {
				$unvalidated_entry = strtok($unvalidated_entry, " ");
			}
			$output_array[$region_proto_sequence][$actual_hit] = $unvalidated_entry;
			if($conf['debug']) {
				trigger_error("DEBUG: $actual_hit is a hit containing $unvalidated_entry !<br>\n", E_USER_NOTICE);
			}
			$hit = 0;
			continue;
		}
	}
	
	$dom->clear();
	return($output_array);
}
?>
