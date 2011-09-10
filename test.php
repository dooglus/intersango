<?php
require_once "util.php";
require_once "voucher.php";

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

test_mtgox_withdraw_fiat_coupon();

?>
