<?php
require_once 'withdraw_utils.php';

if (!$is_verified) {
    throw new Error("Not Verified",
                    "Please <a href=\"?page=identity\">identify yourself</a> before initiating any withdrawal.");
}

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

if (isset($_POST['amount']) && isset($_POST['curr_type'])) {
    
    $curr_type = post('curr_type');
    $amount_disp = post('amount');

    do_withdraw($amount_disp, $curr_type, $voucher_code, $reqid);

    echo "<div class='content_box'>\n";
    echo "<h3>" . sprintf(_("Withdraw %s"), $curr_type) . "</h3>\n";

    if (isset($_POST['voucher']))
        echo "<p>" . sprintf(_("Your voucher for %s is:"),
                             "$amount_disp $curr_type") . "</p><p class='voucher'>$voucher_code</p>\n";
    else
        echo "<p>" . sprintf(_("Your request to withdraw %s has been submitted. Visit your %sprofile%s to check on the status of your request."),
                             "$amount_disp $curr_type",
                             "<a href='?page=profile'>",
                             "</a>") . "</p>\n";
    echo "</div>\n";
}
else {
?>
    <div class='content_box'>
    <h3><?php printf(_("Withdraw %s"), CURRENCY); ?></h3>
<?php
    $balances = fetch_balances($is_logged_in);
    $fiat = $balances[CURRENCY];
    $transferred = fiat_transferred_today($is_logged_in);
    $limit = numstr_to_internal(MAXIMUM_DAILY_FIAT_TRANSFER);
    $available = gmp_sub($limit, $transferred);
    if (gmp_cmp($fiat, $available) > 0) {
        echo "    <p>" . sprintf(_("You can withdraw up to %s each day"),
                                 internal_to_numstr($limit) . " " . CURRENCY) . " (", day_time_range_string(), ")</p>\n";
        if ($transferred) {
            echo "    <p>" . sprintf(_("You have withdrawn %s today"),
                                     internal_to_numstr($transferred) . " " . CURRENCY) . "\n";
            if (gmp_cmp($available, '0') > 0)
                echo "    " . sprintf(_("and so can withdraw up to %s more."),
                                      internal_to_numstr($available) . " " . CURRENCY);
            else
                echo "    " . _("and so cannot withdraw any more until tomorrow.");
            echo "</p>\n";
        }
    }
    if (gmp_cmp($fiat, '0') <= 0)
        echo "    <p>" . sprintf(_("You don't have any %s to withdraw."), CURRENCY) . "</p>\n";
    else if (gmp_cmp($available, '0') > 0) {
        echo "    <p>" . sprintf(_("Enter an amount below to withdraw.  You have %s."),
                                 internal_to_numstr($fiat) . " " . CURRENCY) . "</p>\n";
?>
    <p><?php echo _("We charge no fee for withdrawals."); ?></p>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_name_holder'><?php echo _("PP Account Name"); ?></label>
            <input type='text' id='input_name_holder' name='name_holder' maxlength='18' />

            <label for='input_amount'><?php echo _("Amount"); ?></label>
            <input type='text' id='input_amount' name='amount' value='0.00' />
            
            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='<?php echo CURRENCY; ?>' />
            <input type='hidden' name='is_international' value='false' />
            <input type='submit' value='<?php echo _("Submit"); ?>' />
        </form>
    </p>
    <p><?php echo _("Allow 3-5 working days for payments to pass through clearing."); ?></p>
<?php } ?>
    </div>

<?php
    if (ENABLE_LOCAL_VOUCHERS) {
?>
    <div class='content_box'>
    <h3><?php printf(_("Withdraw %s to Voucher"), CURRENCY); ?></h3>
    <p>
        <?php printf(_("Alternatively, you can withdraw %s as a voucher.
        This will give you a text code which can be redeemed for
        %s credit by any user of this exchange."), CURRENCY, CURRENCY); ?>
    </p>
<?php
        if (gmp_cmp($fiat, $available) > 0) {
            echo "    <p>" . sprintf(_("You can withdraw up to %s each day"),
                                     internal_to_numstr($limit) . " " . CURRENCY) . " (", day_time_range_string(), ")</p>\n";
            if ($transferred) {
                echo "    <p>" . sprintf(_("You have withdrawn %s today"),
                                         internal_to_numstr($transferred) . " " . CURRENCY) . "\n";
                if (gmp_cmp($available, '0') > 0)
                    echo "    " . sprintf(_("and so can withdraw up to %s more."),
                                          internal_to_numstr($available) . " " . CURRENCY);
                else
                    echo "    " . _("and so cannot withdraw any more until tomorrow.");
                echo "</p>\n";
            }
        }
        if (gmp_cmp($fiat, '0') <= 0)
            echo "    <p>" . sprintf(_("You don't have any %s to withdraw."), CURRENCY) . "</p>\n";
        else if (gmp_cmp($available, '0') > 0) {
            echo "    <p>" . sprintf(_("Enter an amount below to withdraw.  You have %s."),
                                     internal_to_numstr($fiat) . " " . CURRENCY) . "</p>\n";
?>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_amount'><?php echo _("Amount"); ?></label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='<?php echo CURRENCY; ?>' />
            <input type='hidden' name='is_international' value='false' />
            <input type='hidden' name='voucher' value='1' />
            <input type='submit' value='<?php echo _("Submit"); ?>' />
        </form>
    </p>
<?php
        }
?>
    </div>
<?php
    }
?>

    <div class='content_box'>
    <h3><?php echo _("Withdraw BTC to Bitcoin Address"); ?></h3>
<?php
    $balances = fetch_balances($is_logged_in);
    $btc = $balances['BTC'];
    $withdrawn = btc_withdrawn_today($is_logged_in);
    $limit = numstr_to_internal(MAXIMUM_DAILY_BTC_WITHDRAW);
    $available = gmp_sub($limit, $withdrawn);
    if (gmp_cmp($btc, $available) > 0) {
        echo "    <p>" . sprintf(_("You can withdraw up to %s BTC each day"), internal_to_numstr($limit)) .
            " (", day_time_range_string(), ").</p>\n";
        if ($withdrawn) {
            echo "    <p>" . sprintf(_("You have withdrawn %s BTC today"), internal_to_numstr($withdrawn)) . "\n";
            if (gmp_cmp($available, '0') > 0)
                echo "    " . sprintf(_("and so can withdraw up to %s BTC more."), internal_to_numstr($available));
            else
                echo "    " . _("and so cannot withdraw any more until tomorrow.");
            echo "</p>\n";
        }
    }
    if (gmp_cmp($btc, '0') <= 0)
        echo "    <p>" . _("You don't have any BTC to withdraw.") . "</p>\n";
    else if (gmp_cmp($available, '0') > 0) {
        echo "    <p>" . sprintf(_("Enter an amount below to withdraw.  You have %s BTC."), internal_to_numstr($btc)) . "</p>\n";
?>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_address'><?php echo _("Address"); ?></label>
            <input type='text' id='input_address' name='address' />
            
            <label for='input_amount'><?php echo _("Amount"); ?></label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='BTC' />
            <input type='submit' value='<?php echo _("Submit"); ?>' />
        </form>
    </p>
<?php
    }
    echo "    </div>\n";

    if (ENABLE_LOCAL_VOUCHERS) { ?>
    <div class='content_box'>
    <h3><?php echo _("Withdraw BTC to Voucher"); ?></h3>
    <p>
        <?php echo _("Alternatively, you can withdraw Bitcoins as a voucher.
        This will give you a text code which can be redeemed for
        Bitcoins by any user of this exchange."); ?>
    </p>
<?php
        if (gmp_cmp($btc, $available) > 0) {
            echo "    <p>" . sprintf(_("You can withdraw up to %s BTC each day"), internal_to_numstr($limit)) .
                " (", day_time_range_string(), ").</p>\n";
            if ($withdrawn) {
                echo "    <p>" . sprintf(_("You have withdrawn %s BTC today"), internal_to_numstr($withdrawn)) . "\n";
                if (gmp_cmp($available, '0') > 0)
                    echo "    " . sprintf(_("and so can withdraw up to %s BTC more."), internal_to_numstr($available));
                else
                    echo "    " . _("and so cannot withdraw any more until tomorrow.");
                echo "</p>\n";
            }
        }
        if (gmp_cmp($btc, '0') <= 0)
            echo "    <p>" . _("You don't have any BTC to withdraw.") . "</p>\n";
        else if (gmp_cmp($available, '0') > 0) {
            echo "    <p>" . sprintf(_("Enter an amount below to withdraw.  You have %s BTC."), internal_to_numstr($btc)) . "</p>\n";
?>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_amount'><?php echo _("Amount"); ?></label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='BTC' />
            <input type='hidden' name='voucher' value='1' />
            <input type='submit' value='<?php echo _("Submit"); ?>' />
        </form>
    </p>
<?php
        }
        echo "    </div>\n";
    }
}
?>
