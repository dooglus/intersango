<div class='content_box'>
<h3>Deposit GBP</h3>
<p>Depositing is free by bank deposit for this month of March. Email sdlkssdsd for details.</p>
</div>

<?php
require 'jsonRPCClient.php';
try {
    $bitcoin = new jsonRPCClient('http://user:password@127.0.0.1:8332/');
    $addy = $bitcoin->agetaccountaddress('');
    ?>
    <div class='content_box'>
    <h3>Deposit BTC</h3>
    <p>You can deposit to <?php echo $addy; ?></p>
    <?php
}
catch (Exception $e) {
    // ... no option to deposit BTC
}
?>
</div>

