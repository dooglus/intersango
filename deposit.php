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
    <p><b>Depositing is free by bank deposit (EFT). You are responsible for paying any incurred fees. If your deposit is insufficient to cover bank fees then it will be denied.</b></p>
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
    <p>For fast 24Hr clearing visit any ANZ bank to deposit funds.</p>
    <p><b>Online Banking select your bank below to login.</b></p>
    <p><a href="https://www.my.commbank.com.au/netbank/Logon/Logon.aspx" target="_blank" >CBA</a> - <a href="https://www.anz.com/INETBANK/bankmain.asp" target="_blank" >ANZ</a> - 
    <a href="https://online.westpac.com.au/esis/Login/SrvPage/?h3&app=wol&referrer=http%3A%2F%2Fwww.westpac.com.au%2FHomepageAlternative%2F" target="_blank" >WESTPAC</a> - 
    <a href="https://ib.nab.com.au/nabib/index.jsp" target="_blank" >NAB</a> - 
    <a href="http://www.google.com.au/" target="_blank" >Other</a></p>
</div>

<div class='content_box'>
    <h3>Deposit BTC</h3>
<?php
if ($addy) {
    echo "    <p>You can deposit to <b>$addy</b></p>\n";
    echo "    <p>The above address is specific to your account.  Each time you deposit, a new address will be generated for you.</p>\n";
    echo "    <p>It takes ", CONFIRMATIONS_FOR_DEPOSIT, " confirmations before funds are added to your account.</p>\n";
} else
    echo "    <p>We are currently experiencing trouble connecting to the bitcoin network.  Please try again in a few minutes.</p>\n";
?>
</div>
