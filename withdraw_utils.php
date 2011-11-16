<?php

require_once 'util.php';
require_once 'voucher.php';

function uk_withdraw($uid, $amount, $curr_type, &$voucher_code, &$reqid)
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
        $ref = post('ref');
        syslog(LOG_NOTICE, "name=$name,bank=$bank,acc=$acc_num,sort=$sort_code,ref=$ref");
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
            INSERT INTO uk_requests (reqid, name, bank, acc_num, sort_code, ref)
            VALUES ('$reqid', '$name', '$bank', '$acc_num', '$sort_code', '$ref');
        ";
        do_query($query);
    }
}

function international_withdraw($uid, $amount, $curr_type, &$reqid)
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

function bitcoin_withdraw($uid, $amount, $curr_type, &$voucher_code, &$reqid)
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
        try {
            $validaddy = bitcoin_validate_address($addy);
        } catch (Exception $e) {
            if ($e->getMessage() != 'Unable to connect.')
                throw $e;
            throw new Problem(_("Sorry..."),
                              _("We are currently experiencing trouble connecting to the Bitcoin network and so cannot verify that you entered a valid Bitcoin address.") .
                              "</p><p>" .
                              _("Your withdrawal request has been cancelled.") .
                              "</p><p>" .
                              _("Please try again in a few minutes."));
        }

        if (!$validaddy['isvalid'])
            throw new Problem(_('Bitcoin says no'), _('That address you supplied was invalid.'));
        syslog(LOG_NOTICE, "address=$addy");

        $we_have = bitcoin_get_balance("*", 0);
        if (gmp_cmp($we_have, $amount) <= 0) {
            $message = sprintf(_("User %s is asking to withdraw %s BTC.  We only have %s BTC."),
                               $uid,
                               internal_to_numstr($amount, BTC_PRECISION),
                               internal_to_numstr($we_have, BTC_PRECISION));
            email_tech(_("Exchange Wallet Balance is Too Low"), $message);
        }

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

function save_details($uid, $amount, $curr_type, &$voucher, &$reqid)
{
    beginlog();
    syslog(LOG_NOTICE, "Withdrawing $amount $curr_type:");
    if ($curr_type == CURRENCY) {
        $is_international = post('is_international') == 'true';
        if (!$is_international) {
            uk_withdraw($uid, $amount, $curr_type, $voucher, $reqid);
            return true;
        }
        else {
            international_withdraw($uid, $amount, $curr_type, $reqid);
            return true;
        }
    }
    else if ($curr_type == 'BTC') {
        bitcoin_withdraw($uid, $amount, $curr_type, $voucher, $reqid);
        return true;
    }
    else {
        throw Error('Invalid currency', 'You cannot withdraw a currency that does not exist.');
    }
    // should never happen!
    return false;
}

function truncate_num($num, $decimal_places)
{
    $trailing_zeroes = 8 - $decimal_places;
    if ($trailing_zeroes == 0) return $num;
    return substr($num, 0, -$trailing_zeroes) . str_repeat('0', $trailing_zeroes);
}

function do_withdraw($amount_disp, $curr_type, &$voucher_code, &$reqid)
{
    global $is_logged_in;

    $amount = numstr_to_internal($amount_disp);

    // dollar amounts should be truncated to cents, but Bitcoins are more divisible
    if ($curr_type == 'BTC')
        $amount = truncate_num($amount, BTC_WITHDRAW_DECIMAL_PLACES);
    else
        $amount = truncate_num($amount, 2);

    curr_supported_check($curr_type);
    order_worthwhile_check($amount, $amount_disp, MINIMUM_WITHDRAW);
    enough_money_check($amount, $curr_type);
    check_withdraw_limit($is_logged_in, $amount, $curr_type);

    if (!save_details($is_logged_in, $amount, $curr_type, $voucher_code, $reqid))
        throw Error('We had to admit it sometime...', 'Stop trading on thie site. Contact the admin FAST.');

    // actually take the money now
    deduct_funds($amount, $curr_type);

    // request is submitted to the queue for the cron job to actually execute (unless it's a voucher)
}

?>
