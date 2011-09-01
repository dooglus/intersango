<?php
require_once 'util.php';
require_once 'voucher.php';

if (isset($_POST['amount']) && isset($_POST['curr_type']))
{
    if(isset($_POST['csrf_token']))
    {
        if($_SESSION['csrf_token'] != $_POST['csrf_token'])
        {
            throw new Error("csrf","csrf token mismatch!");
        }
    }
    else
    {
        throw new Error("csrf","csrf token missing");
    }
}

function show_deposit_voucher_form($code = '')
{ ?>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_code'>Voucher</label>
            <input type='text' onClick='select();' autocomplete='off' id='input_code' name='code' value='<?php echo $code; ?>' />
            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='submit' value='Submit' />
        </form>
    </p>
<?php
}

if (isset($_POST['code'])) {
    echo "<div class='content_box'>\n";
    echo "<h3>Deposit Voucher</h3>\n";
    $code = post('code', '-');
    try {
        redeem_voucher($code, $is_logged_in);
        echo "<p>got any more?</p>\n";
        show_deposit_voucher_form($code);
    } catch (Exception $e) {
        $message = $e->getMessage();
        echo "<p>error: $message</p>\n";
        echo "<p>try again?</p>\n";
        show_deposit_voucher_form($code);
    }
    echo "</div>\n";
} else {
    $uid = $is_logged_in;
    $bitcoin = connect_bitcoin();
    try {
        $addy = @$bitcoin->getaccountaddress((string)$uid);
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
    <h3>Deposit Voucher</h3>
    <p>It's possible to withdraw BTC or AUD as 'vouchers' on the
       withdraw page.  These vouchers can be given to other exchange
       users and redeemed here.
    </p>
    <p>
       If you have received a voucher for this exchange, please
       copy/paste the voucher code into the box below to redeem it:
    </p>
<?php show_deposit_voucher_form(); ?>
</div>

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
        echo "    <p>We are currently experiencing trouble connecting to the Bitcoin network.  Please try again in a few minutes.</p>\n";
    echo "</div>\n";
}
