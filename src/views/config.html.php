<?php
include('header.html.php');
?>
    <section class="<?php echo $status; ?>">
        <h2><?php echo $message; ?></h2>
    </section>

    <section>
        <form action="configurator.php" method="post" id="form_config" name="form_config">
            <?php echo $configform; ?>
            <input type="submit" name="save_conf" value="Speichern">
        </form>
    </section>
<?php
include('footer.html.php');
?>
<?php
