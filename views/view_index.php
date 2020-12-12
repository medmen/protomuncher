<aside class="hidden">
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
</aside>
<section>
	<p>	Bitte laden sie das gewünschte Protokoll hoch (pdf oder xml sind erlaubt)<br>
		Standardmäßig wird das gesamte Protokoll ausgelesen und die voreingestellten Parameter extrahiert.<br>
		Sollten sie abweichende Wünsche haben, rufen sie bitte zuerst den <a href="./configurator.php">Konfigurator</a> auf.
	</p>
</section>

<section>
	<form action="upload.php" method="post" id="form_fileupload" name="form_fileupload" enctype="multipart/form-data">
		<p>
			<input type="hidden" name="step" value="2">
			<input type="file" name="inputpdf" size="50" maxlength="100000" accept="application/pdf application/xml" required><br>
			<input type="submit" name="upload" value="Protokoll hochladen">
		</p>
	</form>
</section>