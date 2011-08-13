<?php

require_once "util.php";

function show_header($page, $is_logged_in, $base = false)
{
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>World Bitcoin Exchange</title>
<?php if ($page == 'trade') { ?>
    <script type='text/javascript' src='js/jquery-1.4.4.min.js'></script>
    <script type='text/javascript' src='js/exchanger.js'></script>
<?php 
        $currencies = array('BTC', 'AUD');
        $rates = array();
        foreach ($currencies as $curr_a) {
            $rates_a = array();
            foreach ($currencies as $curr_b) {
                if ($curr_a == $curr_b)
                    continue;
                $exchange_fields = calc_exchange_rate($curr_b, $curr_a, BASE_CURRENCY::B);        
                if ($exchange_fields) {
                    $curr_b = strtolower($curr_b);
                    $rates_a[$curr_b] = (float)$exchange_fields[2];
                }
            }
            $curr_a = strtolower($curr_a);
            $rates[$curr_a] = $rates_a;
        }
        echo "    <script type='text/javascript'>\n";
        echo "        exchange_rates = ".json_encode($rates).";\n";
        echo "    </script>\n";
    }
    if ($base) echo "    <base href=\"$base\" />\n"; ?>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="icon" type="image/png" href="favicon.png" />
</head>

<?php
if ($page == 'trade')
    if ((isset($_GET['in']) && get('in') == 'BTC') ||
        (isset($_SESSION['currency_in']) && $_SESSION['currency_in'] == 'BTC'))
        echo "<body onload='set_currency_in(\"btc\"); set_currency_out(\"aud\");'>\n";
    else
        echo "<body onload='set_currency_in(\"aud\"); set_currency_out(\"btc\");'>\n";
else
    echo "<body>\n"; ?>
    <img id='flower' src='images/flower.png' />
    <img id='header' src='images/header.png' />
    <img id='skyline' src='images/skyline.png' />
    <div id='main_pane'>
        <div id='links_bg'>
            <div id='content'>
                <div id='content_sideshadow'>
<?php
    show_content_header($is_logged_in);
}

function show_content_header_balances($uid)
{
    $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';

    $balances = fetch_balances($uid);
    $aud = internal_to_numstr($balances['AUD'], 4);
    $btc = internal_to_numstr($balances['BTC'], 4);

    $c_balances = fetch_committed_balances($uid);
    $c_aud = internal_to_numstr($c_balances['AUD'], 4);
    $c_btc = internal_to_numstr($c_balances['BTC'], 4);

    echo "    <div class='content_header_box'>\n";
    echo "        balances:{$spaces}$aud ";
    if ($c_aud > 0) echo "(+$c_aud) ";
    echo "AUD{$spaces}$btc ";
    if ($c_btc > 0) echo "(+$c_btc) ";
    echo "BTC\n";
    echo "    </div>\n";
}

function show_content_header_ticker()
{
    $spaces = '&nbsp;&nbsp;&nbsp;&nbsp;';
    list($vol, $buy, $sell, $last) = get_ticker_data();
    if ($buy > $sell && $buy != 0 && $sell != 0)
        $style = " style='color:#af0;'";
    else
        $style = '';
    $buy_link = "<a $style href=\"?page=trade&in=BTC\">$buy</a>";
    $sell_link = "<a $style href=\"?page=trade&in=AUD\">$sell</a>";
    echo "    <div class='content_header_box'>\n";
    echo "        24h volume:&nbsp;<a href=\"?page=view_trades\">$vol BTC</a>{$spaces}";
    echo "buy:&nbsp;$buy_link${spaces}sell:&nbsp;$sell_link";
    echo "{$spaces}last:&nbsp;$last\n";
    echo "    </div>\n";
}

function show_content_header_time()
{
    echo "    <div class='content_header_box' style='float: right;'>\n";
    echo "        ", date('g:i:sa j-M', time()), "\n";
    echo "    </div>\n";
}

function show_content_header_frozen()
{
    if (is_frozen()) {
        echo "    <div class='content_header_box'>\n";
        echo "        <span style='color: #fff;'>trading on the exchange is currently frozen; no orders will be matched</span>\n";
        if (is_admin()) echo "&nbsp;&nbsp;&nbsp;&nbsp;<a style='color: red;' href=\"?page=freeze\">unfreeze</a>\n";
        echo "    </div>\n";
    }
}

function show_content_header($is_logged_in)
{
    echo "<div class='content_header'>\n";

    show_content_header_time();
    show_content_header_ticker();

    if ($is_logged_in)
        show_content_header_balances($is_logged_in);

    show_content_header_frozen();

    echo "</div>\n";
}

 ?>
