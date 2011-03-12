<div class='content_box'>
<h3>Deposit GBP</h3>
<p>Depositing is free by bank deposit for this month of March. Email sdlkssdsd for details.</p>
</div>

<?php
require 'util.php';
$uid = $_SESSION['uid'];
try {
    $bitcoin = connect_bitcoin();
    $addy = $bitcoin->getaccountaddress($uid);
    ?>
    <div class='content_box'>
    <h3>Deposit BTC</h3>
    <p>You can deposit to <?php echo $addy; ?></p>
    <?php
}
catch (Exception $e) {
    # ... no option to deposit BTC
    # send email to self.
}
?>
</div>

