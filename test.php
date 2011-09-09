<?php
require_once "util.php";
require_once "voucher.php";

function test_aud_commission($aud, $already_paid = '0')
{
    $commission = commission_on_aud(numstr_to_internal($aud), numstr_to_internal($already_paid));
    echo "<li>commission selling BTC for <b>$aud</b> " . CURRENCY . " is <b>", internal_to_numstr($commission), "</b> " . CURRENCY;
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
test_aud_commission('0.0001', '1');
test_aud_commission('0.0001', '0.012');
test_aud_commission('0.0001');
test_aud_commission('0.001');
test_aud_commission('0.01');
test_aud_commission('0.1');
test_aud_commission('1');
test_aud_commission('10');
test_aud_commission('100');
test_aud_commission('1000');
test_aud_commission('10000');
test_aud_commission('10000', '0.012');
test_aud_commission('10000', '1');
test_aud_commission('100000');
test_aud_commission('1000000');
test_aud_commission('10000000');
echo "</ul></div>\n";

function test_voucher_prefix($p)
{
    if (looks_like_mtgox_aud_voucher($p))
        echo "$p: yes<br/>\n";
    else
        echo "$p: no<br/>\n";
}

test_voucher_prefix("MTGOX_CAD_sfsdf");
test_voucher_prefix("MTGOX-CAD-sfsdf");
test_voucher_prefix("MTGOX-CAD");
test_voucher_prefix("MTGOX-CAD--");
?>
