<?php
require_once 'util.php';
require_once 'view_util.php';
require_once 'errors.php';
require_once 'openid.php';

echo "<div class='content_box'>\n";
echo "<h3>Recent Trades</h3>\n";

$query = "
    SELECT txid,
           a_amount,
           a_orderid,
           b_amount,
           b_orderid,
           " . sql_format_date("t.timest") . " AS timest,
           a.uid AS a_uid,
           b.uid AS b_uid
    FROM transactions AS t
    JOIN orderbook AS a
    ON a.orderid = a_orderid
    JOIN orderbook AS b
    ON b.orderid = b_orderid
    WHERE b_amount > 0
          AND t.timest > NOW() - INTERVAL 1 DAY
    ORDER BY txid;
";
$result = do_query($query);
$first = true;
$amount_fiat_total = $amount_btc_total = '0';
$mine = 0;
while ($row = mysql_fetch_assoc($result)) {
    if ($first) {
        $first = false;
        echo "<table class='display_data'>\n";
        echo "<tr>";
        echo "<th>TID</th>";
        if ($is_admin) echo "<th>User</th>";
        echo "<th>" . CURRENCY . "</th>";
        if ($is_admin) echo "<th>User</th>";
        echo "<th>BTC</th>";
        echo "<th>Price</th>";
        echo "<th>Date</th>";
        echo "</tr>";
    }
    
    $txid = $row['txid'];
    $a_amount = $row['a_amount'];
    $a_orderid = $row['a_orderid'];
    $b_amount = $row['b_amount'];
    $b_orderid = $row['b_orderid'];
    $timest = $row['timest'];
    $a_uid = $row['a_uid'];
    $b_uid = $row['b_uid'];
    $price = clean_sql_numstr(bcdiv($a_amount, $b_amount, 4));

    $amount_fiat_total = gmp_add($amount_fiat_total, $a_amount);
    $amount_btc_total = gmp_add($amount_btc_total, $b_amount);

    $a_is_me = ($a_uid == $is_logged_in);
    $b_is_me = ($b_uid == $is_logged_in);

    if ($a_is_me)
        echo active_table_row("active", "?page=view_order&orderid=$a_orderid");
    else if ($b_is_me)
        echo active_table_row("active", "?page=view_order&orderid=$b_orderid");
    else
        echo "<tr>";

    echo "<td>$txid</td>";
    if ($is_admin) active_table_cell_link_to_user_statement($a_uid);
    if ($a_is_me) {
        $mine++;
        echo "<td style='font-weight:bold;'>", internal_to_numstr($a_amount,4), "</td>";
    } else
        echo "<td>", internal_to_numstr($a_amount,4), "</td>";
    if ($is_admin) active_table_cell_link_to_user_statement($b_uid);
    if ($b_is_me) {
        $mine++;
        echo "<td style='font-weight:bold;'>", internal_to_numstr($b_amount,4), "</td>";
    } else
        echo "<td>", internal_to_numstr($b_amount,4), "</td>";
    echo "<td>$price</td>";
    echo "<td>$timest</td>";
    echo "</tr>\n";
}

if ($first)
    echo "<p>There are no recent trades.</p>\n";
else {
    $price = clean_sql_numstr(bcdiv(gmp_strval($amount_fiat_total), gmp_strval($amount_btc_total), 4));
    echo "    <tr>\n";
    if ($is_admin)
        echo "        <td></td><td></td><td>--------</td><td></td><td>--------</td><td>--------</td>\n";
    else
        echo "        <td></td><td>--------</td><td>--------</td><td>--------</td>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "        <td></td>";
    if ($is_admin) echo "        <td></td>";
    echo "        <td>", internal_to_numstr($amount_fiat_total,4), "</td>";
    if ($is_admin) echo "        <td></td>";
    echo "        <td>", internal_to_numstr($amount_btc_total,4), "</td>";
    echo "        <td>$price</td>";
    echo "    </tr>\n";
    echo "</table>\n";

    if ($mine) {
        if ($mine == 1)
            echo "<p>The amount you <span style='font-weight: bold;'>gave</span> is in <span style='font-weight: bold;'>bold</span>.</p>\n";
        else
            echo "<p>The $mine amounts you <span style='font-weight: bold;'>gave</span> are in <span style='font-weight: bold;'>bold</span>.</p>\n";
    }
}

?>
</div>
