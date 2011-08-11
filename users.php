<?php
function show_users($precision)
{
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
    $first = true;
    while ($row = mysql_fetch_assoc($result)) {
        if ($first) {
            $first = false;

            echo "<table class='display_data'>\n";
            echo "<tr>";
            echo "<th></th>";
//          echo "<th></th>";
            echo "<th colspan='2' style='text-align: center;'>AUD</th>";
            echo "<th colspan='2' style='text-align: center;'>BTC</th>";
            echo "</tr>\n";
            echo "<tr>";
            echo "<th>UID</th>";
//          echo "<th>OID</th>";
            echo "<th>On Hand</th>";
            echo "<th>In Book</th>";
            echo "<th>On Hand</th>";
            echo "<th>In Book</th>";
            echo "<th>Registered</th>";
            echo "</tr>\n";
        }

        $uid = $row['uid'];
        $oidlogin = $row['oidlogin'];
        $is_admin = $row['is_admin'];
        $timest = $row['timest'];
        $aud = $row['aud'];
        $btc = $row['btc'];
        $committed = fetch_committed_balances($uid);
        $c_aud = $committed['AUD'];
        $c_btc = $committed['BTC'];

        if ($is_admin)
            echo "<tr style='font-weight: bold'>";
        else
            echo "<tr>";
        echo "<td>$uid</td>";
//      echo "<td>$oidlogin</td>";
        echo "<td>", internal_to_numstr($aud,   $precision), "</td>";
        echo "<td>", internal_to_numstr($c_aud, $precision), "</td>";
        echo "<td>", internal_to_numstr($btc,   $precision), "</td>";
        echo "<td>", internal_to_numstr($c_btc, $precision), "</td>";
        echo "<td>$timest</td>";
        echo "</tr>\n";
    }

    if (!$first) {
        echo "</table>\n";
        echo "<p>Admins are shown in bold type, and at the top of the table.</p>\n";
    }
    echo "</div>\n";
}

show_users($precision = 4);
?>
