<?php
namespace protomuncher;

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

class Ctpdfparser{
    private $conf;

    public function __construct()
    {
        $this->conf['base_filename'] = 'CT_ScanProtocols_cleaned';
        $this->conf['base_fileextension'] = 'html';
        $this->conf['limit_files'] = '';
        $this->conf['valid_entries'] = array('bereich', 'serienbeschreibung','(eff.) mas', 'ctdivol', 'pitch', 'koll.',  'inkrement', 'kern', 'fenster');
        $this->conf['write_big_file'] = true;
        $this->conf['debug'] = false;

        // override conf settings if desired
        $this::getConf();

    }

    /**
     * @param mixed $conf
     */
    public function setConf($conf): void
    {
        $this->conf = $conf;
    }

    /**
     * @return mixed
     */
    public function getConf()
    {
        return $this->conf;
    }
}

// set filename to parse, can be complete filename without dot and suffix, e.g. "Abdomen-1"
// or something like "Abdomen-" if you wish to parse more than 1 file (usually the case) 
$conf_base_filename = 'CT_ScanProtocols_cleaned';
// $conf_base_filename = 'CT_ScanProtocols_cleaned';
// set file extension, NO Dot! (e.g. 'html', NOT '.html')
// @TODO: filter the dot for v1.0
$conf_base_fileextension = 'html';

// you can limit the files to parse by entering their numbers here
// files should be consecutive, otherwise you will only get Garbage out of this
// example: $limit_files = "1,4"; would parse file Abdomen-1.html, Abdomen-2.html ... till 4
// leave empty if you wish to parse 1 file only!
$conf_limit_files = '';

// define nametags for valid entries
// see HTML-source of the Protocol-files for possible entries
// definition: MUST be lowercase!
$conf_valid_entries = array(	'bereich',
						'serienbeschreibung',
						'(eff.) mas',
						'ctdivol',
						'pitch',
						'koll.',
						'inkrement',
						'kern',
						'fenster'						
						);
// setting this to 0, false or no 
// will cause seperate HTML-File-Output for each protocol
// default is on which will write everything into 1 big html file 
// @Todo: implement writing to several files for v1.0
$conf_write_big_file = 1;

// developer-setting, enable debugging-messages
// set to 0, no or false on production site!
$conf_protomuncher_debug = 0;

//################### config ends ####################
// define some globals
global $region, $protocol, $sequence;

// set some starter variables
$wanted_entry = array();
$file_array = array();
$count = 0;

$region = '';
$protocol = '';
$sequence = '';
$new_file = '';
$output = '';

// set execution_time_limit up, increase memory limit
ini_set('max_execution_time', '240');
ini_set('memory_limit', '512M');


if($conf_protomuncher_debug)
{
	error_reporting(E_ALL ^E_NOTICE);
	ini_set ( 'display_errors' , true );
}

// construct the filenames to parse
if(empty($conf_limit_files))
{
	$file_complete_name = $conf_base_filename.'.'.$conf_base_fileextension;
	$file_array[] = $file_complete_name;
}
else
{
	list($start,$end) = explode(',',$conf_limit_files);
	//quick check for correctness
	if($start < 1 or $end < $start)
	{
		trigger_error('LIMIT FILES ist gesetzt, aber fehlerhaft - Abbruch..', E_USER_ERROR);
	} 	
	
	// build filenames
	for ($i = $start; $i <= $end; $i++)
	{
		// we only need odd filenumbers
		if($i % 2 == 0)
		{
			continue;
		}
		$file_complete_name = $conf_base_filename.$i.'.'.$conf_base_fileextension;
		$file_array[] = $file_complete_name;
	}
}

// Create DOM from URL or file
foreach($file_array as $file)
{
	$new_file.= parse_data($file);
}

// parse the new file for named anchors and create links 
// include the html-dom-parser
include_once('simple_html_dom.php');
$index_list = '';
$dom = str_get_dom($new_file);
foreach($dom->find('a') as $named_anchor)
{
	if(isset($named_anchor->name))
	{
		$index_list.='<li><a href="#'.$named_anchor->name.'" onclick="mask_tables(\'#'.$named_anchor->name.'\')">'.$named_anchor->name."</a></li>\n";
	}
}
$new_file = "<ul>".$index_list."</ul>\n<br>\n<hr>\n".$new_file;
$dom->clear(); 
unset($dom);

// beautify the HTML for output
$new_file = <<< EOF
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
       "http://www.w3.org/TR/html4/strict.dtd">
	<html>
		<head>
			<title>Radiologie Intranet - MRT-Protokolle </title>
			<script type="text/javascript">
				function mask_tables(target)
				{
					var main_part = document.getElementById('main_inhalt');
					var items = main_part.getElementsByTagName('div');
					for(i=0; i<items.length; i++)
					{
						items[i].style.display = 'none';
					}
					
					if(target != null && target != "")
					{
						window.location.hash = target;
					}

					if(window.location.hash)
					{
						var anchor = window.location.hash.split("#");
						document.getElementById(anchor[1]).style.display = 'block';	
					}
				}
				</script>
			<!--#include virtual="/header.inc" -->
		</head>
		<body 	onload="treeMenu_init(document.getElementById('menu'), window.name); mask_tables()"
				onunload="window.name = treeMenu_store(document.getElementById('menu'))">
			<div id="navi">
				<!--#include virtual="/navigation.inc" -->
			</div>
			<div id="main">
				<!--#include virtual="/main_top.inc" -->
				 $new_file
							</tr>
						</tbody>
					</table>
				</div>
				<!--#include virtual="/main_bottom.inc" -->
			</div>
			<div id="footer">
				<span style="	width: 33%;
								text-align: left; 
								vertical-align: middle;
								padding: 2em;">
				<!--#config timefmt="%d.%m.%Y, %H:%M" -->
				letztes Update: <!--#echo var="LAST_MODIFIED" -->
				</span>
				<!--#include virtual="/footer.inc" -->
			</div>
		</body>
	</html>
EOF;

// print output to screen for a start
print_r($new_file);

// ### Main part is over, now functions
function parse_data($file)
{
	global $conf_valid_entries, $conf_protomuncher_debug, $region, $sequence, $protocol, $wanted_entry, $output;

	// include the html-dom-parser, it does lots of the magic :) 
	include_once('simple_html_dom.php');

	// read the whole file
	$dom = file_get_dom($file);

	// initialize the output-variable
	$output = '';
	
	// Strip out all images, if any
	foreach($dom->find('img') as $element)
	{
		$element->outertext = '';
	}

	// Strip out Custom-Protocoll desription stuff
	foreach($dom->find('th span.c1') as $element)
	{
		$element->outertext = '';
	}
	
	//Strip out all &nbsp; tags if any (otherwise they would destroy our parsers)
	foreach($dom->find('td') as $td)
	{
		$td->innertext = str_replace('&nbsp;', '', $td->innertext);
	}
	
	// Match a numerated array of wanted fields
	//@TODO: this implementation sucks probably???
	$i = 0;
	foreach($dom->find('tr th.c5') as $wanted_maybe)
	{
		//  check if the th.c5 contains a span.c4 tag, if yes its holds a new region and thus we need to skip  it...
		if($wanted_maybe->find('span.c4'))
		{
			continue;
		}
		// otherwise we check its contents to see if it contains a "wanted" header-entry and note it's position in the array
		$wanted_maybe_trimmed = trim(strtolower($wanted_maybe->innertext));
		if(in_array($wanted_maybe_trimmed, $conf_valid_entries))
		{
			$wanted_entry[$wanted_maybe_trimmed] = $i;
		}
		++$i;
	}
	
	// extract the region/protocol/sequence
	foreach($dom->find('span.c3') as $region_now)
	{
		if($conf_protomuncher_debug)
		{
			trigger_error("DEBUG: REGION is $region_now in dom->find", E_USER_NOTICE);
		}
		$region_now = $region_now->innertext;
		$is_new_region = check_if_changed($region, $region_now);
		if($is_new_region)
		{
			parse_region($is_new_region);
		}

		foreach($dom->find('tr') as $row)
		{
			$protocol_now = $row->find('td.c6', 0);
			$protocol_now = $protocol_now->innertext;
			$is_new_protocol = check_if_changed($protocol, $protocol_now);
			if($is_new_protocol)
			{
				parse_protocol($is_new_protocol);
			}
			
			$sequence_now = $row->find('td.c6', 1);
			$sequence_now = $sequence_now->innertext;
			$is_new_sequence = check_if_changed($sequence, $sequence_now);
			if($is_new_sequence)
			{
				parse_sequence($is_new_sequence);
			}
			// parse the actual Data
			parse_row($row);
		}
	}

	if($conf_protomuncher_debug)
	{
		trigger_error("DEBUG: POST-CHECK is REGION ".$region." PROTOCOL ".$protocol." SEQUENCE ".$sequence, E_USER_NOTICE);
	}

	$dom->clear();
	return $output;
}

function check_if_changed($subject, $new_subject)
{
	global $conf_protomuncher_debug;
	
	// strip included HTML-Tags, clean leading and trailing whitespaces
	$subject = trim(strip_tags($subject));
	$new_subject = trim(strip_tags($new_subject));
	if($conf_protomuncher_debug)
	{
		var_dump($subject);
		var_dump($new_subject);
	}
	
	if($conf_protomuncher_debug)
	{
		trigger_error("DEBUG: check if '".$subject."' matches '".$new_subject."'", E_USER_NOTICE);
	}
	
	if(empty($subject) and empty($new_subject))
	{
		if($conf_protomuncher_debug)
		{
			trigger_error("DEBUG: subject and new_subject empty --> returning false", E_USER_NOTICE);
		}
		return(false);
	}

	if(empty($new_subject))
	{
		if($conf_protomuncher_debug)
		{
			trigger_error("DEBUG: subject exists and new_subject empty --> returning false", E_USER_NOTICE);
		}
		return(false);
	}

	if(empty($subject))
	{
		if($conf_protomuncher_debug)
		{
			trigger_error("DEBUG: subject empty and new_subject exists --> returning new_subject", E_USER_NOTICE);
		}
		return($new_subject);
	}

	
	if($subject != $new_subject)
	{
		if($conf_protomuncher_debug)
		{
			trigger_error("DEBUG: subject and new_subject exist --> returning new_subject", E_USER_NOTICE);
		}
		return($new_subject);
	}

	return(false);
}


function parse_region($this_region)
{
	global $region, $output, $conf_protomuncher_debug;
	$region = $this_region;
	if($conf_protomuncher_debug)
	{
		trigger_error("DEBUG: new region is '$region'", E_USER_NOTICE);
	}
}

function parse_protocol($this_protocol)
{
	global $protocol, $output, $conf_valid_entries, $conf_protomuncher_debug;
	// close table of previous protocol if there was one
	if(!empty($protocol))
	{
		$output.= "\n\t</tbody>\n</table>\n</div>\n\n";
	}
	
	$protocol = $this_protocol;
	if($conf_protomuncher_debug)
	{
		trigger_error("DEBUG: new protocol is '$protocol'", E_USER_NOTICE);
	}

	// beautify the anchor for HTML-compatibility, several escalation-steps are used
	//2nd: a div with an ID, this requires the first character to be a letter, 
	// umlauts are not allowed 
	$sonderzeichen = Array("/ä/","/ö/","/ü/","/Ä/","/Ö/","/Ü/","/ß/","/\s/");
	$replace = Array("ae","oe","ue","Ae","Oe","Ue","ss","_");
	$output.= '<div id="'.preg_replace($sonderzeichen, $replace, $protocol).'">'."\n";
	$output.= '<h3><a name="'.preg_replace($sonderzeichen, $replace, $protocol).'">'.$protocol."</a></h3>\n";
	$output.= "<table>\n\t<thead>\n\t\t<tr>\n\t\t\t\n";
	foreach($conf_valid_entries as $th_field)
	{
		$output.="<th>".strtoupper($th_field)."</th>";
	}
	$output.= "</tr>\n</thead>\n<tfoot><tr><td colspan=\"".count($conf_valid_entries)."\">Erklärungen</td></tr></tfoot>\n<tbody>\n\n";
}

function parse_sequence($this_sequence)
{			
	global $sequence, $output, $conf_protomuncher_debug, $conf_protomuncher_debug;
	// close table-row of previous sequence if there was one
/*	if(!empty($sequence))
	{
		$output.= "\n\t\t</tr>\n";
	}
*/
	$sequence = $this_sequence;
	if($conf_protomuncher_debug)
	{
		trigger_error("DEBUG: new sequence is '$sequence'", E_USER_NOTICE);
	}
}

function parse_row($this_row)
{
	global $conf_valid_entries, $output, $conf_protomuncher_debug, $wanted_entry, $temp_arr;

	if($conf_protomuncher_debug)
	{
		trigger_error("DEBUG: parsing row '$this_row'", E_USER_NOTICE);
	}

	foreach($this_row->find('td.c6') as $potential_hit)
	{
		$potential_hits_array[] = trim(strip_tags($potential_hit->innertext)); // should give an ordered array
	}

	foreach($conf_valid_entries as $valid_entry)
	{
		// exapmle: if $valid_entry = 'FOV', then $wanted_entry[$valid_entry]  == $wanted_entry['FOV'] == some integer (e.g. 4) 
		$array_position = $wanted_entry[$valid_entry];
		if(empty($potential_hits_array[$array_position]))
		{
			if(empty($temp_arr[$array_position]) or $temp_arr[$array_position] == '<td>---</td>')
			{
				$temp_arr[$array_position] = '<td>---</td>';
			}
		}
		else
		{
			if(empty($temp_arr[$array_position]) or $temp_arr[$array_position] == '<td>---</td>')
			{
				$temp_arr[$array_position] = "<td>$potential_hits_array[$array_position]</td>";
			}
			else
			{
				ksort($temp_arr);
				$output.= "<tr>".implode($temp_arr)."</tr>\n";
				$temp_arr = array();
				$temp_arr[$array_position] = "<td>$potential_hits_array[$array_position]</td>";
			}
		}
/*
		ksort($temp_arr);
		print_r($temp_arr); echo "<br>\n";
*/
	}
}

function trimall($str, $charlist = " \t\n\r\0\x0B")
{
  return str_replace(str_split($charlist), '', $str);
}

// Dump modified HTML contents
// echo $dom;
?>