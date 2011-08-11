<?php

function count_transactions($orderid)
{
    $query = "
        SELECT COUNT(*) as count
        FROM transactions
        WHERE (transactions.a_orderid=$orderid
           OR  transactions.b_orderid=$orderid)
          AND  transactions.a_amount != -1
          AND  transactions.b_amount != -1;
    ";
    $result = do_query($query);
    $row = mysql_fetch_assoc($result);
    return $row['count'];
}
    
function display_transactions($uid, $orderid)
{
    global $is_logged_in, $is_admin;

    $ordselq = '';
    if (!$orderid)
        $sort = "DESC";
    else {
        $sort = "ASC";
        $ordselq = " AND orderbook.orderid='$orderid' ";
    }
    $query = "
        SELECT
            orderbook.orderid AS orderid,
            IF(transactions.a_orderid=orderbook.orderid, 'A', 'B') AS who,
            transactions.a_amount AS a_amount,
            transactions.b_amount AS b_amount,
            transactions.a_commission AS a_commission,
            transactions.b_commission AS b_commission,
            orderbook.type AS type,
            orderbook.want_type AS want_type,
            DATE_FORMAT(transactions.timest, '%H:%i %d/%m/%y') AS timest
        FROM transactions
        JOIN orderbook
        ON
            transactions.a_orderid=orderbook.orderid
            OR transactions.b_orderid=orderbook.orderid
        WHERE orderbook.uid='$uid' $ordselq
        ORDER BY transactions.txid $sort;
    ";
    $result = do_query($query);
    $first = true;
    $a_total = 0;
    $b_total = 0;
    $commission_total = 0;
    $count = 0;
    while ($row = mysql_fetch_assoc($result)) {
        $count++;
        $who = $row['who'];
        $a_amount = $row['a_amount'];
        $b_amount = $row['b_amount'];
        $a_commission = $row['a_commission'];
        $b_commission = $row['b_commission'];
        if ($who == 'B') {
            list($a_amount, $b_amount) = array($b_amount, $a_amount);
            $b_commission = $a_commission;
        }
        # skip cancelled orders since we already show those
        if ((int)$b_amount == -1)
            continue;

        if ($first) {
            $first = false;
            ?> <div class='content_box'>
            <h3>
<?php
    if ($is_logged_in == $uid)
        echo "Your trades ";
    else
        echo "Trades ";
    if ($orderid) echo 'for this order'; ?></h3>
            <table class='display_data'>
                <tr>
<?php if (!$orderid) { ?>
                    <th>Order</th>
<?php } ?>
                    <th>You gave</th>
                    <th>You got</th>
                    <th>Commission</th>
                    <th>Price</th>
                    <th>Time</th>
                </tr><?php
        }

        $a_total = gmp_add($a_total, $a_amount);
        $b_total = gmp_add($b_total, $b_amount);
        $commission_total = gmp_add($commission_total, $b_commission);

        $commission_percent = bcdiv(bcmul($b_commission, 100), $b_amount, 3);

        $b_amount = gmp_sub($b_amount, $b_commission);

        $a_amount = internal_to_numstr($a_amount);
        $b_amount = internal_to_numstr($b_amount);
        $b_commission = internal_to_numstr($b_commission);
        $type = $row['type'];
        $want_type = $row['want_type'];
        if ($type == 'BTC')
           $price = $b_amount / $a_amount;
        else
           $price = $a_amount / $b_amount;
        $price = sprintf("%.4f", $price);
        $this_orderid = $row['orderid'];
        $timest = $row['timest'];
        if (!$orderid)
            echo "    ", active_table_row("active", "?page=view_order&orderid=$this_orderid"), "\n";
        else
            echo "    <tr>\n";
        echo "        ";
        if (!$orderid)
            echo "<td>$this_orderid</td>";
        echo "<td>$a_amount $type</td>";
        echo "<td>$b_amount $want_type</td>";
        echo "<td>$b_commission $want_type<br/>(", sprintf("%.3f", $commission_percent), "%)</td>";
        echo "<td>$price</td>";
        echo "<td>$timest</td>\n";
        echo "    </tr>\n";
    }

    // if we showed any table at all
    if (!$first) {
        // if we need to show a summary line
        if ($orderid && $count > 1) {
            $commission_percent = bcdiv(bcmul(gmp_strval($commission_total), 100), gmp_strval($b_total), 3);

            $b_total = gmp_sub($b_total, $commission_total);
            $a_total = internal_to_numstr($a_total);
            $b_total = internal_to_numstr($b_total);
            $commission_total = internal_to_numstr($commission_total);

            if ($type == 'BTC')
                $price = $b_total / $a_total;
            else
                $price = $a_total / $b_total;
            $price = sprintf("%.6f", $price);
            
            echo "    <tr>\n";
            echo "        <td>--------</td><td>--------</td><td>--------</td><td>--------</td>\n";
            echo "        <td></td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "        <td>$a_total $type</td><td>$b_total $want_type</td><td>$commission_total $want_type<br/>(",
                sprintf("%.3f", $commission_percent), "%)</td><td>$price</td>\n";
            echo "        <td></td>\n";
            echo "    </tr>\n";
        }

        echo "</table>\n";
        echo "<p>The 'you got' column is the amount you received after commission was taken off.</p>";
        echo "<p>The 'price' column shows the effective price of the trade, after commission.</p>";
        echo "</div>\n";
    }
}

?>
