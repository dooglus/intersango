<?php

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
            DATE_FORMAT(transactions.timest, '%H%i %d/%m/%y') AS timest
        FROM transactions
        JOIN orderbook
        ON
            transactions.a_orderid=orderbook.orderid
            OR transactions.b_orderid=orderbook.orderid
        WHERE orderbook.uid='$uid' $ordselq
        ORDER BY transactions.timest DESC;
    ";
    $result = do_query($query);
    $first = true;
    while ($row = mysql_fetch_assoc($result)) {
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
                    <th>Description</th>
                    <th>Time</th>
                    <?php if ($orderid == -1) echo '<th></th>'; ?>
                </tr><?php
        }

        $a_amount = internal_to_numstr($a_amount);
        $b_amount = internal_to_numstr($b_amount);
        $type = $row['type'];
        $want_type = $row['want_type'];
        $orderid = $row['orderid'];
        $timest = $row['timest'];
        echo "    <tr>\n";
        echo "        <td>You gave $a_amount $type for <b>$b_amount $want_type</td>\n";
        echo "        <td>$timest</td>\n";
        if ($orderid == -1)
            echo "        <td><a href='?page=view_order&orderid=$orderid'>View order</a></td>\n";
        echo "    </tr>\n";
    }
    if (!$first)
        echo "</table></div>";
}

?>

