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

function uk_withdraw($uid, $amount, $curr_type, &$voucher_code)
{
    $voucher = isset($_POST['voucher']);

    if ($voucher) {
        syslog(LOG_NOTICE, "address=voucher");

        $query = "
            INSERT INTO requests (req_type, uid, amount, curr_type)
            VALUES ('WITHDR', '$uid', '$amount', '$curr_type');
        ";
    } else {
        $name = post('name_holder');
        $bank = post('name_bank');
        $acc_num = post('account_number');
        $sort_code = post('sort_code');
        syslog(LOG_NOTICE, "name=$name,bank=$bank,acc=$acc_num,sort=$sort_code");
        $query = "
        INSERT INTO requests (req_type, uid, amount, curr_type)
        VALUES ('WITHDR', '$uid', '$amount', '$curr_type');
    ";
    }
    endlog();

    do_query($query);
    $reqid = mysql_insert_id();

    if ($voucher)
        $voucher_code = store_new_fiat_voucher_code($reqid);
    else {
        $query = "
            INSERT INTO uk_requests (reqid, name, bank, acc_num, sort_code)
            VALUES ('$reqid', '$name', '$bank', '$acc_num', '$sort_code');
        ";
        do_query($query);
    }
}

function international_withdraw($uid, $amount, $curr_type)
{
    $iban = post('iban');
    $swift = post('swift');
    syslog(LOG_NOTICE, "iban=$iban,swift=$swift");
    endlog();

    $query = "
        INSERT INTO requests (req_type, uid, amount, curr_type)
        VALUES ('WITHDR', '$uid', '$amount', '$curr_type');
    ";
    do_query($query);
    $reqid = mysql_insert_id();
    $query = "
        INSERT INTO international_requests (reqid, iban, swift)
        VALUES ('$reqid', '$iban', '$swift');
    ";
    do_query($query);
}

function bitcoin_withdraw($uid, $amount, $curr_type, &$voucher_code)
{
    $voucher = isset($_POST['voucher']);

    if ($voucher) {
        syslog(LOG_NOTICE, "address=voucher");

        $query = "
            INSERT INTO requests (req_type, uid, amount, curr_type)
            VALUES ('WITHDR', '$uid', '$amount', '$curr_type');
        ";
    } else {
        $addy = post('address');
        $bitcoin = connect_bitcoin();
        try {
            $validaddy = $bitcoin->validateaddress($addy);
        } catch (Exception $e) {
            if ($e->getMessage() != 'Unable to connect.')
                throw $e;
            throw new Problem("Sorry...",
                              "We are currently experiencing trouble connecting to the Bitcoin network and so cannot verify that you entered a valid Bitcoin address.</p><p>Your withdrawal request has been cancelled.</p><p>Please try again in a few minutes.");
        }

        if (!$validaddy['isvalid'])
            throw new Problem('Bitcoin says no', 'That address you supplied was invalid.');
        syslog(LOG_NOTICE, "address=$addy");

        $query = "
            INSERT INTO requests (req_type, uid, amount, curr_type)
            VALUES ('WITHDR', '$uid', '$amount', '$curr_type');
        ";
    }

    endlog();

    do_query($query);
    $reqid = mysql_insert_id();
  
    if ($voucher)
        $voucher_code = store_new_bitcoin_voucher_code($reqid);
    else {
        $query = "
            INSERT INTO bitcoin_requests (reqid, addy)
            VALUES ('$reqid', '$addy');
        ";
        do_query($query);
    }
}

function save_details($uid, $amount, $curr_type, &$voucher)
{
    beginlog();
    syslog(LOG_NOTICE, "Withdrawing $amount $curr_type:");
    if ($curr_type == CURRENCY) {
        $is_international = post('is_international') == 'true';
        if (!$is_international) {
            uk_withdraw($uid, $amount, $curr_type, $voucher);
            return true;
        }
        else {
            international_withdraw($uid, $amount, $curr_type);
            return true;
        }
    }
    else if ($curr_type == 'BTC') {
        bitcoin_withdraw($uid, $amount, $curr_type, $voucher);
        return true;
    }
    else {
        throw Error('Invalid currency', 'You cannot withdraw a currency that does not exist.');
    }
    # should never happen!
    return false;
}

function truncate_num($num, $decimal_places)
{
    $trailing_zeroes = 8 - $decimal_places;
    if ($trailing_zeroes == 0) return $num;
    return substr($num, 0, -$trailing_zeroes) . str_repeat('0', $trailing_zeroes);
}

if (isset($_POST['amount']) && isset($_POST['curr_type'])) {
    
    $uid = user_id();
    $amount_disp = post('amount');
    $curr_type = post('curr_type');
    $amount = numstr_to_internal($amount_disp);
    $voucher = isset($_POST['voucher']);

    // dollar amounts should be truncated to cents, but Bitcoins are more divisible
    if ($curr_type == 'BTC')
        $amount = truncate_num($amount, BTC_WITHDRAW_DECIMAL_PLACES);
    else
        $amount = truncate_num($amount, 2);

    curr_supported_check($curr_type);
    order_worthwhile_check($amount, $amount_disp, MINIMUM_WITHDRAW);
    enough_money_check($amount, $curr_type);
    check_withdraw_limit($uid, $amount, $curr_type);

    if (!save_details($uid, $amount, $curr_type, $voucher_code))
        throw Error('We had to admit it sometime...', 'Stop trading on thie site. Contact the admin FAST.');
    # actually take the money now
    deduct_funds($amount, $curr_type);
    # request is submitted to the queue for the cron job to actually execute

    echo "<div class='content_box'>\n";
    echo "<h3>Withdraw $curr_type</h3>\n";
    if ($voucher)
        echo "<p>Your voucher for $amount_disp $curr_type is:</p><p class='voucher'>$voucher_code</p>\n";
    else
        echo "<p>Your request to withdraw $amount_disp $curr_type has been submitted. Visit your <a href='?page=profile'>profile</a> to check on the status of your request.</p>\n";
    echo "</div>\n";
}
else {
?>
    <div class='content_box'>
    <h3>Withdraw <?php echo CURRENCY; ?> (Australian residents)</h3>
<?php
    $uid = user_id();
    $balances = fetch_balances($uid);
    $fiat = $balances[CURRENCY];
    $transferred = fiat_transferred_today($uid);
    $limit = numstr_to_internal(MAXIMUM_DAILY_FIAT_TRANSFER);
    $available = gmp_sub($limit, $transferred);
    if (gmp_cmp($fiat, $available) > 0) {
        echo "    <p>You can transfer up to ", internal_to_numstr($limit), " " . CURRENCY . " each day (", day_time_range_string(), ")</p>\n";
        if ($transferred) {
            echo "    <p>You have transferred ", internal_to_numstr($transferred), " " . CURRENCY . " today\n";
            if (gmp_cmp($available, '0') > 0)
                echo "    and so can withdraw up to ", internal_to_numstr($available), " " . CURRENCY . " more.";
            else
                echo "    and so cannot withdraw any more until tomorrow.";
            echo "</p>\n";
        }
    }
    if (gmp_cmp($fiat, '0') <= 0)
        echo "    <p>You don't have any " . CURRENCY . " to withdraw.</p>\n";
    else if (gmp_cmp($available, '0') > 0) {
        echo "    <p>Enter an amount below to withdraw.  You have ", internal_to_numstr($fiat), " " . CURRENCY . ".</p>\n";
?>
    <p>We charge no fee.
    You are responsible for paying any incurred fees. If your deposit 
    is insufficient to cover bank fees then it will be denied.</p>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_name_holder'>Name of account holder</label>
            <input type='text' id='input_name_holder' name='name_holder' maxlength='18' />

            <label for='input_name_bank'>Name of the bank</label>
            <input type='text' id='input_name_bank' name='name_bank' />

            <div id='acc_details'>
                <div id='acc_num'>
                    <label for='input_account_number'>Account number</label>
                    <input type='text' class='input_no_block' id='input_account_number' name='account_number' />
                </div>
                <div id='acc_sort'>
                    <label for='input_sort_code'>BSB</label>
                    <input type='text' id='input_sort_code' name='sort_code' />
                </div>
            </div>

            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' value='0.00' />
            
            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='<?php echo CURRENCY; ?>' />
            <input type='hidden' name='is_international' value='false' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    <p>Allow 3-5 working days for payments to pass through clearing.</p>
<?php } ?>
    </div>

<!-- DISABLED
    <div class='content_box'>
    <h3>Withdraw <?php echo CURRENCY; ?> (international)</h3>
    <p>Enter an amount below to submit a withdrawal request. A fee of 20 <?php echo CURRENCY; ?> for amounts below 5000 <?php echo CURRENCY; ?> and 35 <?php echo CURRENCY; ?> otherwise, applies. Your bank may charge an additional processing fee on their end.</p>
    <p>Please also contact support@britcoin.co.uk</p>
    <p>
        <form action='' class='indent_form' method='post'>
            <div id='acc_details'>
                <div id='acc_num'>
                    <label for='input_account_number'>IBAN</label>
                    <input type='text' class='input_no_block' id='input_account_number' name='iban' />
                </div>
                <div id='acc_sort'>
                    <label for='input_sort_code'>BIC/SWIFT</label>
                    <input type='text' id='input_sort_code' name='swift' />
                </div>
            </div>

            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='curr_type' value='<?php echo CURRENCY; ?>' />
            <input type='hidden' name='is_international' value='true' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>
-->

    <div class='content_box'>
    <h3>Withdraw <?php echo CURRENCY; ?> to Voucher</h3>
    <p>
        Alternatively, you can withdraw <?php echo CURRENCY; ?> as a voucher.
        This will give you a text code which can be redeemed for
        <?php echo CURRENCY; ?> credit by any user of this exchange.  Specify the
        amount of <?php echo CURRENCY; ?> to withdraw.
    </p>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='<?php echo CURRENCY; ?>' />
            <input type='hidden' name='is_international' value='false' />
            <input type='hidden' name='voucher' value='1' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>

    <div class='content_box'>
    <h3>Withdraw BTC to Bitcoin Address</h3>
<?php
    $uid = user_id();
    $balances = fetch_balances($uid);
    $btc = $balances['BTC'];
    $withdrawn = btc_withdrawn_today($uid);
    $limit = numstr_to_internal(MAXIMUM_DAILY_BTC_WITHDRAW);
    $available = gmp_sub($limit, $withdrawn);
    if (gmp_cmp($btc, $available) > 0) {
        echo "    <p>You can withdraw up to ", internal_to_numstr($limit), " BTC each day (", day_time_range_string(), ").</p>\n";
        if ($withdrawn) {
            echo "    <p>You have withdrawn ", internal_to_numstr($withdrawn), " BTC today\n";
            if (gmp_cmp($available, '0') > 0)
                echo "    and so can withdraw up to ", internal_to_numstr($available), " BTC more.";
            else
                echo "    and so cannot withdraw any more until tomorrow.";
            echo "</p>\n";
        }
    }
    if (gmp_cmp($btc, '0') <= 0)
        echo "    <p>You don't have any BTC to withdraw.</p>\n";
    else if (gmp_cmp($available, '0') > 0) {
        echo "    <p>Enter an amount below to withdraw.  You have ", internal_to_numstr($btc), " BTC.</p>\n";
?>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_address'>Address</label>
            <input type='text' id='input_address' name='address' />
            
            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='BTC' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>

    <div class='content_box'>
    <h3>Withdraw BTC to Voucher</h3>

    <p>
        Alternatively, you can withdraw Bitcoins as a voucher.
        This will give you a text code which can be redeemed for
        Bitcoins by any user of this exchange.  Specify the
        number of Bitcoins to withdraw.
    </p>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' value='0.00' />

            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='BTC' />
            <input type='hidden' name='voucher' value='1' />
            <input type='submit' value='Submit' />
        </form>
    </p>
<?php
    }
    echo "    </div>\n";
}
?>
