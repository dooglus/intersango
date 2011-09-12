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
            <label for='input_code'><?php echo _("Voucher"); ?></label>
            <input type='text' onClick='select();' autocomplete='off' id='input_code' name='code' value='<?php echo $code; ?>' />
            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='submit' value='Submit' />
        </form>
    </p>
<?php
}

if (isset($_POST['code'])) {
    echo "<div class='content_box'>\n";
    echo "<h3>" . _("Deposit Voucher") . "</h3>\n";
    $code = post('code', '-');
    try {
        redeem_voucher($code, $is_logged_in);
        echo "<p>" . _("got any more?") . "</p>\n";
        show_deposit_voucher_form($code);
    } catch (Exception $e) {
        $message = $e->getMessage();
        echo "<p>" . _("error") . ": $message</p>\n";
        echo "<p>" . _("try again?") . "</p>\n";
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
     <h3><?php echo _("Deposit Voucher"); ?></h3>
    <p><?php printf(
    _("It's possible to withdraw BTC or %s as 'vouchers' on the
       withdraw page.  These vouchers can be given to other exchange
       users and redeemed here."), CURRENCY); ?>
    </p>
    <p><?php printf(
    _("If you have received a voucher for this exchange, please
       copy/paste the voucher code into the box below to redeem it.")); ?>
    </p>
    <p><?php printf(
    _("We also accept %sMTGOX-%s-...%s vouchers for instant transfers
       of %s from MtGox to this exchange."), "<strong>", CURRENCY, "</strong>", CURRENCY); ?>
    <p><?php printf(
    _("Note that there will be a %s%% fee (capped at %s) taken for processing of MtGox vouchers."),
    COMMISSION_PERCENTAGE_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER,
    sprintf("%.2f %s", COMMISSION_CAP_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER, CURRENCY)); ?>
    </p>
<?php show_deposit_voucher_form(); ?>
</div>

<div class='content_box'>
    <h3><?php echo _("Deposit") . " " . CURRENCY; ?></h3>
    <p><b><?php echo _("Depositing is free by bank deposit (EFT). You are responsible for paying any incurred fees. If your deposit is insufficient to cover bank fees then it will be denied."); ?></b></p>
    <p><?php printf(_("You will need to quote <strong>%s</strong> in the transaction's reference field."), $deposref); ?></p>
    <table class='display_data'>
        <tr>
            <td><?php echo _("Account title") . ":"; ?></td>
            <td><?php echo DEPOSIT_BANK_ACCOUNT_TITLE; ?></td>
        </tr>
        <tr>
            <td><?php echo _("Bank") . ":"; ?></td>
            <td><?php echo DEPOSIT_BANK_NAME; ?></td>
        </tr>
        <tr>
            <td><?php echo _("Account number") . ":"; ?></td>
            <td><?php echo DEPOSIT_BANK_ACCOUNT_NUMBER; ?></td>
        </tr>
        <tr>
            <td><?php echo _("BSB") . ":"; ?></td>
            <td><?php echo DEPOSIT_BANK_BRANCH_ID; ?></td>
        </tr>
        <tr>
            <td><?php echo _("Reference") . ":"; ?></td>
            <td><?php echo $deposref; ?></td>
        </tr>
    </table>
    <p><?php echo _("Allow 3-5 working days for payments to pass through clearing."); ?></p>
    <p><b><?php echo _("Online Banking select your bank below to login."); ?></b></p>
    <p>
      <a target="_blank"
        href="https://www.my.commbank.com.au/netbank/Logon/Logon.aspx"
      >CBA</a>
      -
      <a target="_blank"
        href="https://www.anz.com/INETBANK/bankmain.asp"
      >ANZ</a>
      -
      <a target="_blank"
        href="https://online.westpac.com.au/esis/Login/SrvPage/?h3&app=wol&referrer=http%3A%2F%2Fwww.westpac.com.au%2FHomepageAlternative%2F"
      >WESTPAC</a>
      -
      <a target="_blank"
        href="https://ib.nab.com.au/nabib/index.jsp"
      >NAB</a>
      -
      <a target="_blank"
        href="http://www.google.com.au/"
      >Other</a>
    </p><br/>
    <strong><p>For fast 24Hr clearing visit any ANZ bank to deposit funds.</p></strong>
    <p>(you will be required to use your<strong><a href="?page=profile"> 'User ID'</a></strong> as reference)</p><br/>

</div>

<div class='content_box'>
    <h3><?php echo _("Deposit"); ?> BTC</h3>
<?php
    if ($addy) {
        echo "    <p>" . sprintf(_("You can deposit to %s"), "<b>$addy</b>") . "</p>\n";
        echo "    <p>" . _("The above address is specific to your account.  Each time you deposit, a new address will be generated for you.") . "</p>\n";
        echo "    <p>" . sprintf(_("It takes %s confirmations before funds are added to your account."), CONFIRMATIONS_FOR_DEPOSIT) . "</p>\n";
    } else
        echo "    <p>" . _("We are currently experiencing trouble connecting to the Bitcoin network.  Please try again in a few minutes.") . "</p>\n";
    echo "</div>\n";
}
