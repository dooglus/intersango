<?php
function show_users($precision)
{
    $omit_zero_balances = true;

    echo "<div class='content_box'>\n";
    echo "<h3>Users</h3>\n";

    $query = "
    SELECT
        u.uid, oidlogin, is_admin, timest, a.amount as aud, b.amount as btc
    FROM
        users as u
    JOIN
        purses as a
    ON
        a.uid = u.uid AND a.type = 'AUD'
    JOIN
        purses as b
    ON
        b.uid = u.uid AND b.type = 'BTC'
    ORDER BY
        is_admin DESC, u.uid;
    ";

    $result = do_query($query);
    $aud_total = $c_aud_total = $t_aud_total = '0';
    $btc_total = $c_btc_total = $t_btc_total = '0';
    $first = true;
    $count_users = $count_funded_users = 0;
    while ($row = mysql_fetch_assoc($result)) {
        $uid = $row['uid'];
        $oidlogin = $row['oidlogin'];
        $is_admin = $row['is_admin'];
        $timest = $row['timest'];
        $aud = $row['aud'];
        $btc = $row['btc'];
        $committed = fetch_committed_balances($uid);
        $c_aud = $committed['AUD'];
        $c_btc = $committed['BTC'];
        $t_aud = gmp_add($aud, $c_aud);
        $t_btc = gmp_add($btc, $c_btc);
        if ($uid == '1')
            $uid = "fees";
        else
            $count_users++;

        if ($omit_zero_balances && $aud == 0 && $c_aud == 0 && $btc == 0 && $c_btc == 0)
            continue;

        if ($uid != 'fees')
            $count_funded_users++;

        if ($first) {
            $first = false;

            echo "<table class='display_data'>\n";
            echo "<tr>";
            echo "<th></th>";
//          echo "<th></th>";
            echo "<th colspan='3' style='text-align: center;'>AUD</th>";
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

        $aud_total   = gmp_add($aud_total,   $aud);
        $c_aud_total = gmp_add($c_aud_total, $c_aud);
        $t_aud_total = gmp_add($t_aud_total, $t_aud);
        $btc_total   = gmp_add($btc_total,   $btc);
        $c_btc_total = gmp_add($c_btc_total, $c_btc);
        $t_btc_total = gmp_add($t_btc_total, $t_btc);

        if ($is_admin)
            echo "<tr style='font-weight: bold'>";
        else
            echo "<tr>";
        echo "<td>$uid</td>";
//      echo "<td>$oidlogin</td>";
        echo "<td>", internal_to_numstr($aud,   $precision), "</td>";
        echo "<td>", internal_to_numstr($c_aud, $precision), "</td>";
        echo "<td>", internal_to_numstr($t_aud, $precision), "</td>";
        echo "<td>", internal_to_numstr($btc,   $precision), "</td>";
        echo "<td>", internal_to_numstr($c_btc, $precision), "</td>";
        echo "<td>", internal_to_numstr($t_btc, $precision), "</td>";
//      echo "<td>$timest</td>";
        echo "</tr>\n";
    }

    if (!$first) {
        echo "<tr><td></td><td>--------</td><td>--------</td><td>--------</td><td>--------</td><td>--------</td><td>--------</td></tr>\n";
        echo "<tr>\n";
        echo "<td></td>";
        echo "<td>", internal_to_numstr($aud_total,   $precision), "</td>";
        echo "<td>", internal_to_numstr($c_aud_total, $precision), "</td>";
        echo "<td>", internal_to_numstr($t_aud_total, $precision), "</td>";
        echo "<td>", internal_to_numstr($btc_total,   $precision), "</td>";
        echo "<td>", internal_to_numstr($c_btc_total, $precision), "</td>";
        echo "<td>", internal_to_numstr($t_btc_total, $precision), "</td>";
        echo "</tr>\n";
        echo "</table>\n";
        echo "<p>Admins are shown in bold type, and at the top of the table.</p>\n";
    }

    echo "<p>There are $count_funded_users users with funds, and $count_users in total.</p>\n";

    echo "</div>\n";
}

show_users($precision = 4);
?>
