<?php
include('header.html.php');

if ($failure) {
    echo "<h2 class=error>Fehler</h2>";
    echo "<p> Umwandlung fehlgeschlagen. Der Prozess gab folgende Fehlermeldung(en) zurück:<br>";
    foreach ($failure as $failure_text) {
        echo $this->esc($failure_text)."<br />";
    }
    echo "</p>";
} else {
    echo "<h2 class=success>Erfolg</h2>";
    echo "<p> Umwandlung erfolgreich. Hier das Ergebnis:<br>";
    echo "<pre>$this->esc($res)</pre>";
    echo "</p>";
}

include('footer.html.php');