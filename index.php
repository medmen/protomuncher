<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Protomuncher - MRT-Protokoll-Exorzist </title>
<script type="text/javascript" src="./js/jquery-2.1.4.min.js"></script>
<script type="text/javascript" src="./js/handle_upload.js"></script>

<style type="text/css">
.waiting {
    background-image: url('waiting.gif');
}
.hidden {
	display: none;
}
.visible {
	display: inherit;
}
</style>
</head>

<body>
<div id="spinner" class="waiting hidden">
</div>

<div style="margin: 30px 10%;">
<div class="hidden">
	<h1>Protomuncher</h1>
	<h2>was macht das?</h2>
	<h3>Step 1 - upload PDF</h3>
		<p>After Uploading, the pdf-file will undergo a quick check, then will be converted to a HTML file
		using the software pdftohtml. <br />
		This will create a single complex HTML-file for every page the PDF file contains.
		As you can see, this step will create rather a lot of potentially huge files, so please make the PDF file
		as small as possible (i.e. dont't export all scan protocols as 1 PDF file!!!)</p>
		<h3>Step 2 - Transform</h3>
		<p>	The PDF will be converted to a HTML file
		using the software "pdftohtml". This will create a single complex HTML-file for every page the PDF file contains.
		<pre> pdftohtml -c {TARGET} {TARGET}.html'</pre>
		</p>
		<h3>Step 3 - Reduce HTML</h3>
		<p class="help">
		We have gained a large complex HTML file <br />
		Next step will include some magic (assisted by the simple_html_dom project) to 
		<ul>
			<li>find the wanted data based on the selection you make below</li>
			<li>extract this data in a structure called "array"</li>
			<li>re-assemble it in a new table, surroundings can be configured as you like</li>
		</ul>
		</p>
		<h3>Step 4 - Clean-Up & Enjoy</h3>
		<p> all uploaded and converted files will be expunged<br> 
		exciting isn't it?
		</p>
</div>
				
<div>
<?php 
$conf_settings = parse_ini_file("config.ini.php", true);
/**
echo "<h4> config </h4>";
print_r($conf_settings);
echo "<hr>";
**/
?>
<form action="upload.php" method="post" id="form_fileupload" name="form_fileupload" enctype="multipart/form-data">
	<p>
		<input type="hidden" name="step" value="2">
		<input type="file" name="inputpdf" size="50" maxlength="100000" accept="application/pdf" required><br>
		<input type="submit" name="upload" value="PDF hochladen">
	</p>

<?php 
foreach($conf_settings as $cnf) {
	if('select' == $cnf['typ']) {
		echo '<label for="'.$cnf['name'].'">'.$cnf['hilfe'].'</label>';
		echo '<select multiple name="'.$cnf['name'].'" id="'.$cnf['name'].'">';
		foreach($cnf[$cnf['name']] as $val) {
			echo '<option value="'.$val.'" selected="selected">'.$val.'</option>';
		}
		echo "</select><br>";
	} else {
		echo '<label for="'.$cnf['name'].'">'.$cnf['hilfe'].'</label>';
		echo '<input type="'.$cnf['typ'].'" name="'.$cnf['name'].'" id="'.$cnf['name'].'" value="'.$cnf[$cnf['name']].'">';
		echo "<br>";
	}
}
?>

</form>
</div>
</div>
<?php
function write_php_ini($array, $file) {
	$res = array();
	foreach($array as $key => $val) {
		if(is_array($val)) {
			$res[] = "[$key]";
			foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
		}
		else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
	}
	safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave) {
	if ($fp = fopen($fileName, 'w')) {
		$startTime = microtime(TRUE);
		do {
			$canWrite = flock($fp, LOCK_EX);
			// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			if(!$canWrite) usleep(round(rand(0, 100)*1000));
		} while ((!$canWrite)and((microtime(TRUE)-$startTime) < 5));

		//file was locked so now we can store information
		if ($canWrite) {
			fwrite($fp, $dataToSave);
			flock($fp, LOCK_UN);
		}
		fclose($fp);
	}
}
?>

</body>
</html>