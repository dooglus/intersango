<?php

function show_users()
{
    $omit_zero_balances = true;
    $omit_very_low_balances = true;

    echo "<div class='content_box'>\n";
    echo "<h3>Users</h3>\n";

    $query = "
    SELECT
        u.uid, oidlogin, is_admin, timest, a.amount as fiat, b.amount as btc
    FROM
        users as u
    JOIN
        purses as a
    ON
        a.uid = u.uid AND a.type = '" . CURRENCY . "'
    JOIN
        purses as b
    ON
        b.uid = u.uid AND b.type = 'BTC'
    ORDER BY
        is_admin DESC, u.uid;
    ";

    $result = do_query($query);
    $fiat_total = $c_fiat_total = $t_fiat_total = '0';
    $btc_total = $c_btc_total = $t_btc_total = '0';
    $first = true;
    $count_users = $count_funded_users = $count_low_balance_users = 0;
    while ($row = mysql_fetch_assoc($result)) {
        $uid = $row['uid'];
        $oidlogin = $row['oidlogin'];
        $is_admin = $row['is_admin'];
        $timest = $row['timest'];
        $fiat = $row['fiat'];
        $btc = $row['btc'];
        $committed = fetch_committed_balances($uid);
        $c_fiat = $committed[CURRENCY];
        $c_btc = $committed['BTC'];
        $t_fiat = gmp_add($fiat, $c_fiat);
        $t_btc = gmp_add($btc, $c_btc);
        if ($uid == '1')
            $uid = "fees";
        else
            $count_users++;

        if ($omit_zero_balances && $fiat == 0 && $c_fiat == 0 && $btc == 0 && $c_btc == 0)
            continue;

        if ($first) {
            $first = false;

            echo "<table class='display_data'>\n";
            echo "<tr>";
            echo "<th></th>";
//          echo "<th></th>";
            echo "<th colspan='3' style='text-align: center;'>" . CURRENCY . "</th>";
            echo "<th colspan='3' style='text-align: center;'>BTC</th>";
            echo "</tr>\n";
            echo "<tr>";
            echo "<th>UID</th>";
//          echo "<th>OID</th>";
            echo "<th>On Hand</th>";
            echo "<th>In Book</th>";
            echo "<th>Total</th>";
            echo "<th>On Hand</th>";
            echo "<th>In Book</th>";
            echo "<th>Total</th>";
//          echo "<th>Registered</th>";
            echo "</tr>\n";
        }

        $fiat_total   = gmp_add($fiat_total,   $fiat);
        $c_fiat_total = gmp_add($c_fiat_total, $c_fiat);
        $t_fiat_total = gmp_add($t_fiat_total, $t_fiat);
        $btc_total   = gmp_add($btc_total,   $btc);
        $c_btc_total = gmp_add($c_btc_total, $c_btc);
        $t_btc_total = gmp_add($t_btc_total, $t_btc);

        if ($uid != 'fees') {
            $count_funded_users++;
            if ($fiat < 1e5 && $c_fiat < 1e5 && $btc < 1e5 && $c_btc < 1e5) {
                $count_low_balance_users++;
                if ($omit_very_low_balances)
                    continue;
            }
        }

        if ($uid == 'fees')
            $url = "?page=commission";
        else
            $url = "?page=statement&user=$uid";

        if ($is_admin)
            active_table_row('me', $url);
        else
            active_table_row('active', $url);

        echo "<td>$uid</td>";
//      echo "<td>$oidlogin</td>";
        echo "<td>", internal_to_numstr($fiat,   FIAT_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($c_fiat, FIAT_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($t_fiat, FIAT_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($btc,    BTC_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($c_btc,  BTC_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($t_btc,  BTC_PRECISION), "</td>";
//      echo "<td>$timest</td>";
        echo "</tr>\n";
    }

    if (!$first) {
        echo "<tr><td></td><td>--------</td><td>--------</td><td>--------</td><td>--------</td><td>--------</td><td>--------</td></tr>\n";
        active_table_row('me', "?page=statement&user=all");
        echo "<td></td>";
        echo "<td>", internal_to_numstr($fiat_total,   FIAT_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($c_fiat_total, FIAT_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($t_fiat_total, FIAT_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($btc_total,    BTC_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($c_btc_total,  BTC_PRECISION), "</td>";
        echo "<td>", internal_to_numstr($t_btc_total,  BTC_PRECISION), "</td>";
        echo "</tr>\n";
        echo "</table>\n";
        echo "<p>Admins are shown in bold type, and at the top of the table.</p>\n";
    }

    echo "<p>There are $count_funded_users users with funds, and $count_users in total.</p>\n";
    if ($omit_very_low_balances && $count_low_balance_users)
        echo "<p>$count_low_balance_users user(s) have very low balances, and aren't shown above.</p>\n";

    $bitcoin = connect_bitcoin();
    $balance = $bitcoin->getbalance('');

    echo "<p>The Bitcoin wallet has ", internal_to_numstr($balance, BTC_PRECISION), " BTC.<br/></p>\n";

    $diff = gmp_sub($t_btc_total, $balance);

    $cmp = gmp_cmp($diff, 0);

    if ($cmp == 0)
        echo "<p>That's the exact right amount</p>\n";
    else if ($cmp > 0)
        echo "<p>That's ", internal_to_numstr($diff, BTC_PRECISION), " BTC less than is on deposit</p>\n";
    else
        echo "<p>That's ", internal_to_numstr(gmp_mul("-1", $diff), BTC_PRECISION), " BTC more than is on deposit</p>\n";

    echo "</div>\n";
}

show_users();
?>
