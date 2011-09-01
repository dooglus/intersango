<?php

function show_statement($userid)
{
    $show_increments = false;
    $show_prices = true;

    $aud_precision = 2;
    $btc_precision = 4;
    $price_precision = 4;

    echo "<div class='content_box'>\n";
    echo "<h3>Statement (UID $userid)</h3>\n";

    if ($userid == 'all')
        $check_userid = "";
    else
        $check_userid = "uid='$userid' AND";

    $query = "
        SELECT
            txid, a_orderid AS orderid,
            a_amount AS gave_amount, 'AUD' AS gave_curr,
            (b_amount-b_commission) AS got_amount,  'BTC' AS got_curr,
            NULL as reqid,  NULL as req_type,
            NULL as amount, NULL as curr_type, NULL as addy, NULL as voucher, NULL as final, NULL as bank, NULL as acc_num,
            " . sql_format_date('transactions.timest') . " AS date,
            transactions.timest as timest
        FROM
            transactions
        JOIN
            orderbook
        ON
            orderbook.orderid = transactions.a_orderid
        WHERE
            $check_userid
            b_amount != -1

    UNION

        SELECT
            txid, b_orderid AS orderid,
            b_amount AS gave_amount, 'BTC' AS gave_curr,
            (a_amount-a_commission) AS got_amount,  'AUD' AS got_curr,
            NULL, NULL,
            NULL, NULL, NULL, NULL, NULL, NULL, NULL,
            " . sql_format_date('transactions.timest') . " AS date,
            transactions.timest as timest
        FROM
            transactions
        JOIN
            orderbook
        ON
            orderbook.orderid=transactions.b_orderid
        WHERE
            $check_userid
            b_amount != -1

    UNION

        SELECT
            NULL, NULL,
            NULL, NULL,
            NULL, NULL,
            requests.reqid,  req_type,
            amount, curr_type, addy, voucher, status = 'FINAL', bank, acc_num,
            " . sql_format_date('timest') . " AS date,
            timest
        FROM
            requests
        LEFT JOIN
            bitcoin_requests
        ON
            requests.reqid = bitcoin_requests.reqid
        LEFT JOIN
            voucher_requests
        ON
            (requests.reqid = voucher_requests.reqid OR
             requests.reqid = voucher_requests.redeem_reqid)
        LEFT JOIN
            uk_requests
        ON
            requests.reqid = uk_requests.reqid
        WHERE
            $check_userid
            status != 'CANCEL'

    ORDER BY
        timest
    ";

    $first = true;
    $result = do_query($query);
    $aud = 0;
    $btc = 0;

    $first = false;
    echo "<table class='display_data'>\n";
    echo "<tr>";
    echo "<th>Date</th>";
    echo "<th>Description</th>";
    if ($show_prices)
        echo "<th>Price</th>";
    if ($show_increments)
        echo "<th>+/-</th>";
    echo "<th>BTC</th>";
    if ($show_increments)
        echo "<th>+/-</th>";
    echo "<th>AUD</th>";
    echo "</tr>";

    echo "<tr>";
    echo "<td></td>";
    echo "<td></td>";
    if ($show_prices)
        echo "<td></td>";
    if ($show_increments)
        echo "<td></td>";
    printf("<td>%.{$btc_precision}f</td>", 0);
    if ($show_increments)
        echo "<td></td>";
    printf("<td>%.{$aud_precision}f</td>", 0);
    echo "</tr>\n";

    $all_final = true;
    while ($row = mysql_fetch_array($result)) {

        echo "<tr>";
        echo "<td>{$row['date']}</td>";

        if (isset($row['txid'])) {
            $txid = $row['txid'];
            $orderid = $row['orderid'];
            $gave_amount = $row['gave_amount'];
            $gave_curr = $row['gave_curr'];
            $got_amount = $row['got_amount'];
            $got_curr = $row['got_curr'];

            if ($got_curr == 'BTC')
                printf("<td>Buy %.{$btc_precision}f %s for %.{$aud_precision}f %s</td>",
                       internal_to_numstr($got_amount, $btc_precision), $got_curr,
                       internal_to_numstr($gave_amount, $aud_precision), $gave_curr);
            else
                printf("<td>Sell %.{$btc_precision}f %s for %.{$aud_precision}f %s</td>",
                       internal_to_numstr($gave_amount, $btc_precision), $gave_curr,
                       internal_to_numstr($got_amount, $aud_precision), $got_curr);

            if ($got_curr == 'BTC') {
                $aud = gmp_sub($aud, $gave_amount);
                $btc = gmp_add($btc, $got_amount);
                $price = bcdiv($gave_amount, $got_amount, $price_precision);
                if ($show_prices)
                    printf("<td>%.{$price_precision}f</td>", $price);
                if ($show_increments)
                    printf("<td>+ %.{$btc_precision}f</td>", internal_to_numstr($got_amount,  $btc_precision));
                printf("<td> %.{$btc_precision}f</td>",  internal_to_numstr($btc,         $btc_precision));
                if ($show_increments)
                    printf("<td>- %.{$aud_precision}f</td>", internal_to_numstr($gave_amount, $aud_precision));
                printf("<td> %.{$aud_precision}f</td>",  internal_to_numstr($aud,         $aud_precision));
            } else {
                $aud = gmp_add($aud, $got_amount);
                $btc = gmp_sub($btc, $gave_amount);
                $price = bcdiv($got_amount, $gave_amount, $price_precision);
                if ($show_prices)
                    printf("<td>%.{$price_precision}f</td>", $price);
                if ($show_increments)
                    printf("<td>-%.{$btc_precision}f</td>", internal_to_numstr($gave_amount, $btc_precision));
                printf("<td>%.{$btc_precision}f</td>",  internal_to_numstr($btc,         $btc_precision));
                if ($show_increments)
                    printf("<td>+%.{$aud_precision}f</td>", internal_to_numstr($got_amount,  $aud_precision));
                printf("<td>%.{$aud_precision}f</td>",  internal_to_numstr($aud,         $aud_precision));
            }
        } else {
            $reqid = $row['reqid'];
            $req_type = $row['req_type'];
            $amount = $row['amount'];
            $curr_type = $row['curr_type'];
            $voucher = $row['voucher'];
            $final = $row['final'];
            // echo "final is $final<br/>\n";

            if (!$final)
                $all_final = false;

            if ($req_type == 'DEPOS') { /* deposit */
                $title = '';
                if ($voucher)
                    $title = sprintf("from voucher &quot;%s&quot;", $voucher);

                if ($curr_type == 'BTC') { /* deposit BTC */
                    $btc = gmp_add($btc, $amount);
                    printf("<td><strong title='%s'>%s%s %.{$btc_precision}f BTC%s</strong></td>",
                           $title,
                           $final ? "" : "* ",
                           $voucher ? "Redeem" : "Deposit",
                           internal_to_numstr($amount, $btc_precision),
                           $final ? "" : " *");
                    if ($show_prices)
                        printf("<td></td>");
                    if ($show_increments)
                        printf("<td>+%.{$btc_precision}f</td>", internal_to_numstr($amount, $btc_precision));
                    printf("<td>%.{$btc_precision}f</td>",  internal_to_numstr($btc,    $btc_precision));
                    if ($show_increments)
                        printf("<td></td>");
                    printf("<td></td>");
                } else {        /* deposit AUD */
                    $aud = gmp_add($aud, $amount);
                    printf("<td><strong title='%s'>%s%s %.{$aud_precision}f AUD%s</strong></td>",
                           $title,
                           $final ? "" : "* ",
                           $voucher ? "Redeem" : "Deposit",
                           internal_to_numstr($amount, $aud_precision),
                           $final ? "" : " *");
                    if ($show_prices)
                        printf("<td></td>");
                    if ($show_increments)
                        printf("<td></td>");
                    printf("<td></td>");
                    if ($show_increments)
                        printf("<td>+%.{$aud_precision}f</td>", internal_to_numstr($amount, $aud_precision));
                    printf("<td>%.{$aud_precision}f</td>",  internal_to_numstr($aud,    $aud_precision));
                }
            } else {            /* withdrawal */
                if ($curr_type == 'BTC') { /* withdraw BTC */
                    $btc = gmp_sub($btc, $amount);
                    $addy = $row['addy'];
                    if ($addy)
                        $title = sprintf("to Bitcoin address &quot;%s&quot;", $addy);
                    else if ($voucher) {
                        $title = sprintf("to %svoucher &quot;%s&quot;",
                                         $final ? "" : "unredeemed ",
                                         $voucher);
                    }
                    
                    printf("<td><strong title='%s'>%s%s %.{$btc_precision}f BTC%s</strong></td>",
                           $title,
                           $final ? "" : "* ",
                           $voucher ? "Voucher" : "Withdraw",
                           internal_to_numstr($amount, $btc_precision),
                           $final ? "" : " *");
                    if ($show_prices)
                        printf("<td></td>");
                    if ($show_increments)
                        printf("<td>-%.{$btc_precision}f</td>", internal_to_numstr($amount, $btc_precision));
                    printf("<td>%.{$btc_precision}f</td>",  internal_to_numstr($btc,    $btc_precision));
                    if ($show_increments)
                        printf("<td></td>");
                    printf("<td></td>");
                } else {        /* withdraw AUD */
                    $aud = gmp_sub($aud, $amount);
                    $title = '';
                    if ($voucher) {
                        $title = sprintf("to %svoucher &quot;%s&quot;",
                                         $final ? "" : "unredeemed ",
                                         $voucher);
                    } else
                        $title = sprintf("to account %s at %s", $row['acc_num'], $row['bank']);

                    printf("<td><strong title='%s'>%s%s %.{$aud_precision}f AUD%s</strong></td>",
                           $title,
                           $final ? "" : "* ",
                           $voucher ? "Voucher" : "Withdraw",
                           internal_to_numstr($amount, $aud_precision),
                           $final ? "" : " *");
                    if ($show_prices)
                        printf("<td></td>");
                    if ($show_increments)
                        printf("<td></td>");
                    printf("<td></td>");
                    if ($show_increments)
                        printf("<td>-%.{$aud_precision}f</td>", internal_to_numstr($amount, $aud_precision));
                    printf("<td>%.{$aud_precision}f</td>",  internal_to_numstr($aud,    $aud_precision));
                }
            }
        }

        echo "</tr>";
    }

    echo "</table>\n";
    if (!$all_final) {
        echo "<p>Items marked with '*' are not yet final.</p>\n";
        echo "<p>Any such withdrawals and vouchers can be cancelled.</p>\n";
        echo "<p>Any such deposits are pending, and should be finalised within a minute or two.</p>\n";
    }
    echo "</div>";
}

if ($is_admin && isset($_GET['user']))
    show_statement(get('user'));
else
    show_statement($is_logged_in);

?>
