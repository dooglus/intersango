<?php
require_once "util.php";
require_once "voucher.php";
require_once "wbx_api.php";

function test_fiat_commission($fiat, $already_paid = '0')
{
    $commission = commission_on_fiat(numstr_to_internal($fiat), numstr_to_internal($already_paid));
    echo "<li>commission selling BTC for <b>$fiat</b> " . CURRENCY . " is <b>", internal_to_numstr($commission), "</b> " . CURRENCY;
    if ($already_paid)
        echo " if $already_paid was already paid";
    echo "<br/>\n";
}

function test_btc_commission($btc, $already_paid = '0')
{
    $commission = commission_on_btc(numstr_to_internal($btc), numstr_to_internal($already_paid));
    echo "<li>commission buying <b>$btc</b> BTC is <b>", internal_to_numstr($commission), "</b> " . CURRENCY;
    if ($already_paid)
        echo " if $already_paid was already paid";
    echo "<br/>\n";
}

function test_commission()
{
    echo "<div class='content_box'>\n";
    echo "<h3>Rates</h3>\n";
    show_commission_rates();
    echo "</div>\n";

    echo "<div class='content_box'>\n";
    echo "<h3>Commission buying BTC</h3>\n";
    echo "<p>rate is ", COMMISSION_PERCENTAGE_FOR_BTC, "%",
        " and cap is ", COMMISSION_CAP_IN_BTC, " BTC</p>\n";
    echo "<ul>\n";
    test_btc_commission('0.000000001');
    test_btc_commission('0.00000001');
    test_btc_commission('0.0000001');
    test_btc_commission('0.000001');
    test_btc_commission('0.00001');
    test_btc_commission('0.0001', '1');
    test_btc_commission('0.0001', '0.012');
    test_btc_commission('0.0001');
    test_btc_commission('0.001');
    test_btc_commission('0.01');
    test_btc_commission('0.1');
    test_btc_commission('1');
    test_btc_commission('10');
    test_btc_commission('100');
    test_btc_commission('1000');
    test_btc_commission('10000');
    test_btc_commission('10000', '0.012');
    test_btc_commission('10000', '1');
    test_btc_commission('100000');
    test_btc_commission('1000000');
    test_btc_commission('10000000');
    test_btc_commission('100000000');
    test_btc_commission('1000000000');
    test_btc_commission('10000000000');
    test_btc_commission('100000000000');
    test_btc_commission('1000000000000');
    test_btc_commission('10000000000000');
    test_btc_commission('100000000000000');
    test_btc_commission('1000000000000000');
    test_btc_commission('10000000000000000');
    test_btc_commission('100000000000000000');
    test_btc_commission('1000000000000000000');
    test_btc_commission('10000000000000000000');
    test_btc_commission('100000000000000000000');
    test_btc_commission('1000000000000000000000');
    echo "</ul></div>\n";

    echo "<div class='content_box'>\n";
    echo "<h3>Commission selling BTC</h3>\n";
    echo "<p>rate is ", COMMISSION_PERCENTAGE_FOR_FIAT, "%",
        " and cap is ", COMMISSION_CAP_IN_FIAT, " " . CURRENCY . "</p>\n";
    echo "<ul>\n";
    test_fiat_commission('0.0001', '1');
    test_fiat_commission('0.0001', '0.012');
    test_fiat_commission('0.0001');
    test_fiat_commission('0.001');
    test_fiat_commission('0.01');
    test_fiat_commission('0.1');
    test_fiat_commission('1');
    test_fiat_commission('10');
    test_fiat_commission('100');
    test_fiat_commission('1000');
    test_fiat_commission('10000');
    test_fiat_commission('10000', '0.012');
    test_fiat_commission('10000', '1');
    test_fiat_commission('100000');
    test_fiat_commission('1000000');
    test_fiat_commission('10000000');
    echo "</ul></div>\n";
}

function test_voucher_prefix($p)
{
    if (looks_like_mtgox_fiat_voucher($p))
        echo "$p: yes<br/>\n";
    else
        echo "$p: no<br/>\n";
}

function test_voucher_prefixes()
{
    test_voucher_prefix("MTGOX_CAD_sfsdf");
    test_voucher_prefix("MTGOX-CAD-sfsdf");
    test_voucher_prefix("MTGOX-CAD");
    test_voucher_prefix("MTGOX-CAD--");
}

function test_voucher_comm($v)
{
    echo "commission on voucher for " . internal_to_numstr($v,2) . " is " .
        internal_to_numstr(commission_on_deposit_mtgox_fiat_voucher($v),4) . "<br/>\n";
}

function test_voucher_comms()
{
    test_voucher_comm(numstr_to_internal("0.01"));
    test_voucher_comm(numstr_to_internal("0.12"));
    test_voucher_comm(numstr_to_internal("1.23"));
    test_voucher_comm(numstr_to_internal("12.34"));
    test_voucher_comm(numstr_to_internal("123.40"));
    test_voucher_comm(numstr_to_internal("1234.00"));
    test_voucher_comm(numstr_to_internal("12345.00"));
}

// test_commission();
// test_voucher_prefixes();
// test_voucher_comms();

function test_mtgox_withdraw_fiat_coupon()
{
    $mtgox = new MtGox_API(MTGOX_KEY, MTGOX_SECRET);
    $ret = $mtgox->deposit_coupon("MTGOX-USD-EUSCF-JFLF2-ALZ7F-AE50E");
    // $ret = $mtgox->withdraw_fiat_coupon('0.01');
    var_dump($ret);
}

// test_mtgox_withdraw_fiat_coupon();

function test_gettext()
{
    require_once "localization.php";
    echo _("Hello World!") . "\n";
}

// test_gettext();

function test_price($fiat, $btc)
{
    echo "full: " .
        bcdiv($fiat, $btc, 8) . 
        " rounded: " .
        fiat_and_btc_to_price($fiat, $btc, 'round') .
        "; up: " .
        fiat_and_btc_to_price($fiat, $btc, 'up') .
        "; down: " .
        fiat_and_btc_to_price($fiat, $btc, 'down') .
        "<br>\n";
}

function test_prices()
{
    echo "<div class='content_box'>\n";
    echo "<h3>Prices</h3><p>\n";
    // test_price(200, 6);
    // test_price(200, 3);
    // test_price(1, 7);
    test_price( "4999", "100000000");
    test_price( "5000", "100000000");
    test_price( "5001", "100000000");
    test_price( "9999", "100000000");
    test_price("10000", "100000000");
    test_price("10001", "100000000");
    echo "</p></div>\n";
}

function test_api_show_output($title, $results)
{
    echo "<div class='content_box'>\n";
    echo "<h3>API Results - $title</h3>\n";

    echo "<pre>\n";
    var_dump($results);
    echo "</pre>\n";

    echo "</div>\n";
}

function test_api_info($wbx)
{
    $ret = $wbx->info();
    test_api_show_output("Info", $ret);
}

function test_api_withdraw_btc_voucher($wbx)
{
    $ret = $wbx->withdraw_btc_voucher("0.70");
    test_api_show_output("Withdraw BTC Voucher", $ret);
    if ($wbx->ok()) return $ret['voucher'];
}

function test_api_withdraw_fiat_voucher($wbx)
{
    $ret = $wbx->withdraw_fiat_voucher("0.70");
    test_api_show_output("Withdraw Fiat Voucher", $ret);
    if ($wbx->ok()) return $ret['voucher'];
}

function test_api_redeem_voucher($wbx, $voucher)
{
    $ret = $wbx->redeem_voucher($voucher);
    test_api_show_output("Redeem Voucher $voucher", $ret);
}

function test_api_add_order($wbx, $have_amount, $have_currency, $want_amount, $want_currency)
{
    $ret = $wbx->add_order($have_amount, $have_currency, $want_amount, $want_currency);
    test_api_show_output("Add Order ($have_amount $have_currency => $want_amount $want_currency)", $ret);
    if ($wbx->ok()) return $ret['orderid'];
}

function test_api_cancel_order($wbx, $orderid)
{
    $ret = $wbx->cancel_order($orderid);
    test_api_show_output("Cancel Order $orderid", $ret);
}

function test_api_vouchers($wbx)
{
    // make vouchers
    $btc_voucher = test_api_withdraw_btc_voucher($wbx);
    $fiat_voucher = test_api_withdraw_fiat_voucher($wbx);
    test_api_info($wbx);

    // redeem vouchers
    test_api_redeem_voucher($wbx, $btc_voucher);
    test_api_redeem_voucher($wbx, $fiat_voucher);
    test_api_info($wbx);

    // attempt to re-redeem vouchers
    test_api_redeem_voucher($wbx, $btc_voucher);
    test_api_redeem_voucher($wbx, $fiat_voucher);
    test_api_info($wbx);
}

function test_api_orders($wbx)
{
    $orderid1 = test_api_add_order($wbx, 1, 'BTC', 2.5, 'AUD');
    $orderid2 = test_api_add_order($wbx, 2, 'aud', 1, 'btc');

    test_api_cancel_order($wbx, $orderid1);
    test_api_cancel_order($wbx, $orderid2);
    test_api_cancel_order($wbx, $orderid1);
}

function test_api()
{
    global $is_logged_in;

    // the API tries to get a lock on our user.  this will block if we're already locked
    if ($is_logged_in) try { release_lock($is_logged_in); } catch (Exception $e) { echo $e->getMessage(); }
        
    try {
        $wbx = new WBX_API(API_KEY, API_SECRET);

        test_api_info($wbx);
        // test_api_vouchers($wbx);
        test_api_orders($wbx);
    }
    catch (Exception $e) {
        echo "caught Exception: {$e->getMessage()}<br/>\n";
    }

    // re-obtain the lock.  switcher will later try to unlock it
    if ($is_logged_in)
        if (BLOCKING_LOCKS)
            wait_for_lock_if_no_others_are_waiting($is_logged_in);
        else
            get_lock_without_waiting($is_logged_in);
}

test_api();

?>
