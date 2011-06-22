<?php
require_once 'util.php';
$uid = user_id();
$bitcoin = connect_bitcoin();
$addy = $bitcoin->getaccountaddress((string)$uid);

$query = "
    SELECT deposref
    FROM users
    WHERE uid='$uid';
";
$result = do_query($query);
$row = get_row($result);
$deposref = $row['deposref'];
?>

<div class='content_box'>
    <h3>Deposit GBP</h3>
    <p><b>NOTICE: We will be changing bank accounts on July 21st. Your funds are safe and secure and we are still accepting deposits to the current bank account. However, please be aware that in the future our bank details will change and before making a deposit please make sure the details you are referring are up-to-date.</b></p>
    <p>Depositing is free by bank deposit.
You are responsible for paying any incurred fees. If your deposit 
is insufficient to cover bank fees then it will be denied.</p>
    <p>You will need to quote <?php echo $deposref; ?> in the transaction's reference field.</p>
    <table class='display_data'>
        <tr>
            <td>Account title:</td>
            <td>Mr Taaki</td>
        </tr>
        <tr>
            <td>Bank:</td>
            <td>Lloyds TSB</td>
        </tr>
        <tr>
            <td>Account number:</td>
            <td>22939560</td>
        </tr>
        <tr>
            <td>Branch sort code:</td>
            <td>30 96 93</td>
        </tr>
        <tr>
            <td>Reference:</td>
            <td><?php echo $deposref; ?></td>
        </tr>
    </table>
    <p>Allow 3-5 working days for payments to pass through clearing.</p>
</div>

<div class='content_box'>
    <h3>Deposit BTC</h3>
    <p>You can deposit to <?php echo $addy; ?></p>
    <p>It takes 6 confirmations before funds are added to your account.</p>
</div>

