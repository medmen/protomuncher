<?php
namespace protomuncher;
require_once(__DIR__ . '/../vendor/autoload.php');

use Medoo\Medoo;

//we need db

// Initialize
$database = new Medoo([
    'database_type' => 'sqlite',
    'database_file' => __DIR__ . '/../conf/config.sqlite'
]);

$configurator = new Configurator($database);
$status = $message = '';

if(isset($_POST['save_conf'])) {
    $success = $configurator->form2conf($_POST);
    if($success) {
        $message = 'Konfiguration erfolgreich gespeichert';
        $status = 'success';
    } else {
        $status = 'failure';
        $message = 'Fehler beim Speichern der Konfiguration!';
    }
}
?>

<html lang="de">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Protomuncher - Konfigurator </title>
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
<section class="<?php echo $status;?>">
        <h2><?php echo $message;?></h2>
    </section>

    <section>
        <form action="./configurator.php" method="post" id="form_config" name="form_config">
                <?php echo $configurator->conf2form(); ?>
                <input type="submit" name="save_conf" value="Speichern">
        </form>
    </section>
</body>
</html>