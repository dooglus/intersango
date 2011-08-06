<?php
require_once 'util.php';
$uid = user_id();
$bitcoin = connect_bitcoin();
try {
    $addy = $bitcoin->getaccountaddress((string)$uid);
} catch (Exception $e) {
    if ($e->getMessage() != 'Unable to connect.')
        throw $e;
    $addy = '';
}

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
    <h3>Deposit AUD</h3>
    <p><b>NOTICE: Please be aware that our bank details have changed; before making a deposit please make sure the details you are referring to are up-to-date. Funds already deposited into the old account will still be deposited. </b></p>
    <p>Depositing is free by bank deposit. You are responsible for paying any incurred fees. If your deposit is insufficient to cover bank fees then it will be denied.</p>
    <p>You will need to quote <?php echo $deposref; ?> in the transaction's reference field.</p>
    <table class='display_data'>
        <tr>
            <td>Account title:</td>
            <td>High Net Worth Property PTY LTD</td>
        </tr>
        <tr>
            <td>Bank:</td>
            <td>ANZ</td>
        </tr>
        <tr>
            <td>Account number:</td>
            <td>2034-65422</td>
        </tr>
        <tr>
            <td>BSB:</td>
            <td>014-506</td>
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
<?php
if ($addy) {
    echo "    <p>You can deposit to <b>$addy</b></p>\n";
    echo "    <p>The above address is specific to your account.  Each time you deposit, a new address will be generated for you.</p>\n";
    echo "    <p>It takes ", confirmations_for_deposit(), " confirmations before funds are added to your account.</p>\n";
} else
    echo "    <p>We are currently experiencing trouble connecting to the bitcoin network.  Please try again in a few minutes.</p>\n";
?>
</div>
