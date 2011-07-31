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
    $ordselq = '';
    if ($orderid != -1)
        $ordselq = " AND orderbook.orderid='$orderid' ";
    $query = "
        SELECT
            orderbook.orderid AS orderid,
            IF(transactions.a_orderid=orderbook.orderid, 'A', 'B') AS who,
            transactions.a_amount AS a_amount,
            transactions.b_amount AS b_amount,
            orderbook.type AS type,
            orderbook.want_type AS want_type,
            DATE_FORMAT(transactions.timest, '%H:%i %d/%m/%y') AS timest
        FROM transactions
        JOIN orderbook
        ON
            transactions.a_orderid=orderbook.orderid
            OR transactions.b_orderid=orderbook.orderid
        WHERE orderbook.uid='$uid' $ordselq
        ORDER BY transactions.txid DESC;
    ";
    $result = do_query($query);
    $first = true;
    $a_total = 0;
    $b_total = 0;
    $count = 0;
    while ($row = mysql_fetch_assoc($result)) {
        $count++;
        $who = $row['who'];
        $a_amount = $row['a_amount'];
        $b_amount = $row['b_amount'];
        if ($who == 'B')
            list($a_amount, $b_amount) = array($b_amount, $a_amount);
        # skip cancelled orders since we already show those
        if ((int)$b_amount == -1)
            continue;
        if ($first) {
            $first = false;
            ?> <div class='content_box'>
            <h3>Your trades <?php if ($orderid != -1) echo 'for this order'; ?></h3>
            <table class='display_data'>
                <tr>
                    <th>You gave</th>
                    <th>You got</th>
                    <th>Effective Price</th>
                    <th>Time</th>
                    <?php if ($orderid == -1) echo '<th></th>'; ?>
                </tr><?php
        }

        $a_total += $a_amount;
        $b_total += $b_amount;
        $a_amount = internal_to_numstr($a_amount);
        $b_amount = internal_to_numstr($b_amount);
        $type = $row['type'];
        $want_type = $row['want_type'];
        if ($type == 'BTC')
           $price = $b_amount / $a_amount;
        else
           $price = $a_amount / $b_amount;
        $price = sprintf("%.6f", $price);
        $this_orderid = $row['orderid'];
        $timest = $row['timest'];
        echo "    <tr>\n";
        echo "        <td>$a_amount $type</td><td>$b_amount $want_type</td><td>$price</td>\n";
        echo "        <td>$timest</td>\n";
        if ($orderid == -1)
            echo "        <td><a href='?page=view_order&orderid=$this_orderid'>View</a></td>\n";
        echo "    </tr>\n";
    }
    if (!$first) {
        if ($orderid != -1 && $count > 1) {
            $a_total = internal_to_numstr($a_total);
            $b_total = internal_to_numstr($b_total);

            if ($type == 'BTC')
                $price = $b_total / $a_total;
            else
                $price = $a_total / $b_total;
            $price = sprintf("%.6f", $price);
            
            echo "    <tr>\n";
            echo "        <td>--------</td><td>--------</td><td>--------</td>\n";
            echo "        <td></td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "        <td>$a_total $type</td><td>$b_total $want_type</td><td>$price</td>\n";
            echo "        <td></td>\n";
            echo "    </tr>\n";
        }

        echo "</table></div>";
    }
}

?>
