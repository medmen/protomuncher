<?php
// set execution_time_limit up
ini_set('max_execution_time', '240');

/** when debugging, consider enabling next lines
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Protomuncher - MRT-Protokoll-Exorzist - Ergebnisse</title>
<?php

$step1 = upload_pdf();

if(isset($step1['error'])) {
	echo '<h2 class="error">FEHLER</h2><p>'.$step1['message']."</p>\n";
	if(isset($_POST['conf_protomuncher_debug']) && $_POST['conf_protomuncher_debug'] == 1) {
		echo implode("<br>\n", $step1['debug']);
	}
	die;
} 

echo '<h2 class="success">Schritt1 - Upload erfolgreich</h2>'."\n";
$step2 = transform_pdf_to_html();

if(isset($_POST['conf_protomuncher_debug']) && $_POST['conf_protomuncher_debug'] == 1) {
	echo implode("<br>\n", $step2['debug']);
}

if(isset($step2['error'])) {
	echo '<h2 class="error">FEHLER</h2><p>'.$step2['message']."</p>\n";
	die();
}

echo '<h2 class="success">Schritt2 - Umwandlung in HTML erfolgreich</h2>'."\n";
$step3 = extract_data();

if(isset($step3['error'])) {
	echo '<h2 class="error">FEHLER</h2><p>'.$step3['message']."</p>\n";
} else {
	echo '<h2 class="success">Schritt3 - Datenexorzismus erfolgreich</h2>
			<p>Die extrahierten Daten sehen folgendermassen aus:</p>'."\n";
	echo "<pre>".format_data('markdown', $step3);"</pre>\n";
}


function upload_pdf() {
	try {
		// Undefined | Multiple Files | $_FILES Corruption Attack
		// If this request falls under any of them, treat it invalid.
		if (
				!isset($_FILES['inputpdf']['error']) ||
				is_array($_FILES['inputpdf']['error'])
		) {
			throw new RuntimeException('Invalid parameters.');
		}
	
		// Check $_FILES['inputpdf']['error'] value.
		switch ($_FILES['inputpdf']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				throw new RuntimeException('No file sent.');
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				throw new RuntimeException('Exceeded filesize limit.');
			default:
				throw new RuntimeException('Unknown errors.');
		}
	
		// You should also check filesize here.
		if ($_FILES['inputpdf']['size'] > 10000000) {
			throw new RuntimeException('Exceeded filesize limit.');
		}
	
		// DO NOT TRUST $_FILES['inputpdf']['mime'] VALUE !!
		// Check MIME Type by yourself.
		/**
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if (false === $ext = array_search(
				$finfo->file($_FILES['inputpdf']['tmp_name']),
				array(
						'jpg' => 'image/jpeg',
						'png' => 'image/png',
						'gif' => 'image/gif',
				),
				true
		)) {
			throw new RuntimeException('Invalid file format.');
		}
		**/
		$step1['debug'][] = 'file: '.$file['tmp_name']."\n";
		
		//remove old files in upload dir - this will silently fail if permissions are incorrect!
		array_map('unlink', glob("uploads/*")); // glob leaves hidden files alone, just as we want
		
		if(move_uploaded_file($_FILES['inputpdf']['tmp_name'], 'uploads/target.pdf'))
		{
			$step1['message'] = 'Upload erfolgreich';
			$step1['file'] = $uploaddir.'target.pdf';
			return($step1);
		} else {
			throw new RuntimeException('Unknown error on move.');
		}
		
	
	} catch (RuntimeException $e) {
		$step1['error'] = true;
		$step1['message'] = $e->getMessage();
		return($step1);
	}
}

function transform_pdf_to_html() {
	$cmd1 = exec('which pdftohtml');
	$step2['debug'][] = 'CMD1: '.$cmd1."\n";
	$path = realpath('./uploads');
	$cmd2 = $cmd1.' -c -i -enc UTF-8 '.$path.'/target.pdf '.$path.'/target.html';
    $cmd3 = exec($cmd2);
    $step2['debug'][] = 'CMD2: '.$cmd2."\n";
    $step2['debug'][] = 'CMD2 - returned: '.$cmd3."\n";

    $step2['message'] = 'Transformation erfolgreich';
    $step2['file'] = $uploaddir.'target.html';

    return($step2);
}

function extract_data() {
	require_once 'parser.php';
	$file_list = prepare_parsing();
	natcasesort($file_list); // important! keep natural order 
	foreach($file_list as $file) {
		// echo "parsing $file <br>\n";
		if(!is_array($arr_data)) {
			$arr_data = array();
		}
		$arr_data = array_merge($arr_data, parse_data($file));
	}	
	return($arr_data);
}

function format_data($schema, $data) {
	switch($schema){
		case 'html':
			$tbl = '<table>'.PHP_EOL;
			// TODO: read or pass config here
			$tblhead = '<thead><th>Wichtung</th><th>Ebene</th><th>Schichten</th><th>GAP</th><th>FOV</th><th>Dicke</th><th>TR</th><th>TE</th><th>MATRIX</th><th>Sonstiges</th></thead>'.PHP_EOL;
			$region = '';
			$protocol = '';
			foreach($data as $tblrow) {
				if($region != $tblrow['region']) {
					$tbl.= '</table>'.PHP_EOL.'<h2>'.$tblrow['region'].'</h2><table>'.PHP_EOL.$tblhead;
					$region = $tblrow['region'];
				}
				
				if($protocol != $tblrow['protocol']) {
					$tbl.= '</table>'.PHP_EOL.'<h3>'.$tblrow['protocol'].'</h3><table>'.PHP_EOL.$tblhead;
					$protocol = $tblrow['protocol'];
				}
				
				$tbl.= "<tr>
							<td>".$tblrow['sequence']."</td>
							<td>".$tblrow['direction']."</td>
							<td>".$tblrow['schichten']."</td>
							<td>".$tblrow['distanzfaktor']."</td>
							<td>".$tblrow['fov auslese']."</td>
							<td>".$tblrow['schichtdicke']."</td>
							<td>".$tblrow['tr']."</td>
							<td>".$tblrow['te']."</td>
							<td>".$tblrow['basis-auflösung']."</td>
						</tr>".PHP_EOL;
			}
			$tbl.= '</table>'.PHP_EOL;
			return($tbl);
			break;
			
		case 'markdown':
			$tbl = '<pre>'.PHP_EOL;
			// TODO: read or pass config here
			$tblhead = '^  Wichtung  ^  Ebene  ^  Schichten  ^  GAP  ^  FOV  ^  Dicke  ^  TR  ^  TE  ^  MATRIX  ^ Messdauer ^ Sonstiges ^'.PHP_EOL;
			$region = '';
			$protocol = '';
			foreach($data as $tblrow) {
				if($region != $tblrow['region']) {
					$tbl.= '</pre>'.PHP_EOL.'<h2>'.$tblrow['region'].'</h2><pre>'.PHP_EOL.$tblhead;
					$region = $tblrow['region'];
				}
		
				if($protocol != $tblrow['protocol']) {
					$tbl.= '</pre>'.PHP_EOL.'<h3>'.$tblrow['protocol'].'</h3><pre>'.PHP_EOL.$tblhead;
					$protocol = $tblrow['protocol'];
				}
		
				$tbl.= '| '.$tblrow['sequence'].'  |  '
							.$tblrow['direction'].'  |  '
							.$tblrow['schichten'].'  |  '
							.$tblrow['distanzfaktor'].'  |  '
							.$tblrow['fov auslese'].'  |  '
							.$tblrow['schichtdicke'].'  |  '
							.$tblrow['tr'].'  |  '
							.$tblrow['te'].'  |  '
							.$tblrow['basis-auflösung'].'  |  '
							.$tblrow['messdauer'].'  |  '.PHP_EOL;
			}
			$tbl.= '</pre>'.PHP_EOL;
			return($tbl);
			break;

		case 'vardump':
		default:
			return(var_export($data));
			break;
	}
}
?>