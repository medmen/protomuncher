<?php
include('header.html.php');
?>
<section>
    <p>
        Bitte laden sie das gewünschte Protokoll hoch (pdf oder xml sind erlaubt)<br>
        Standardmäßig wird das gesamte Protokoll ausgelesen und die voreingestellten Parameter extrahiert.<br>
        Sollten sie abweichende Wünsche haben, rufen sie bitte zuerst den <a href="./configurator.php">Konfigurator</a>
        auf.<br>
        Für Hilfe und weitere Informationen klicken Sie einfach <a href="./" onclick="toggleVisibility(about)">hier</a>.
    </p>
</section>

<section>
    <form
            action="<?php echo htmlspecialchars($this->esc($url_path) . 'upload.php', ENT_QUOTES, 'UTF-8'); ?>"
            method="post"
            id="form_fileupload"
            name="form_fileupload"
            enctype="multipart/form-data">
        <p>
            <input type="radio" name="geraet" id="ct" value="2">
            <label for="ct">CT</label><br>

            <input type="radio" name="geraet" id="mrt" value="1">
            <label for="mrt">MRT</label><br>

            <input type="file" name="inputpdf" size="50" maxlength="100000" accept="application/pdf,application/xml"
                   required><br>
            <input type="submit" name="upload" value="Protokoll hochladen">
        </p>
    </form>
</section>
<?php
include('footer.html.php');
?>
