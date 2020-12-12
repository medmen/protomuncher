<?php
include('header.html.php');
?>
    <form
    action="<?php echo htmlspecialchars($request->server['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>"
    method="post"
    id="form_fileupload"
    name="form_fileupload"
    enctype="multipart/form-data">
    <p>
        <input type="hidden" name="step" value="2">
        <input type="radio" name="ct" id="modlity" value="ct">
        <label for="ct">CT</label><br>

        <input type="radio" name="mrt" id="modylity" value="mrt">
        <label for="mrt">MRT</label><br>

        <input type="file" name="inputpdf" size="50" maxlength="100000" accept="application/pdf application/xml" required><br>
        <input type="submit" name="upload" value="Protokoll hochladen">
    </p>
</form>

<?php
include('footer.html.php');
?>
