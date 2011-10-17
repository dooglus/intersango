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
            " . sql_format_date("transactions.timest") . " AS timest
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
        // skip cancelled orders since we already show those
        if ((int)$b_amount == -1)
            continue;

        if ($first) {
            $first = false;
            ?> <div class='content_box'>
            <h3>
<?php
    if ($is_logged_in == $uid)
        echo _("Your trades") . " ";
    else
        echo _("Trades") . " ";
    if ($orderid) echo _('for this order'); ?></h3>
            <table class='display_data'>
                <tr>
<?php if (!$orderid) { ?>
                    <th class='right'><?php echo _("Order"); ?></th>
<?php } ?>
                    <th class='right'><?php echo _("You gave"); ?></th>
                    <th class='right'><?php echo _("You got"); ?></th>
                    <th class='right'><?php echo _("Commission"); ?></th>
                    <th class='right'><?php echo _("Price"); ?></th>
                    <th class='center'><?php echo _("Time"); ?></th>
                </tr><?php
        }

        $a_total = gmp_add($a_total, $a_amount);
        $b_total = gmp_add($b_total, $b_amount);
        $commission_total = gmp_add($commission_total, $b_commission);

        if ($b_amount)
            $commission_percent = bcdiv(bcmul($b_commission, 100), $b_amount, 3);
        else
            $commission_percent = 0;

        $b_amount = gmp_sub($b_amount, $b_commission);

        $type = $row['type'];
        $want_type = $row['want_type'];
        $price = 0;
        if ($type == 'BTC') {
            if ($a_amount) $price = fiat_and_btc_to_price($b_amount, $a_amount);
        } else
            if ($b_amount) $price = fiat_and_btc_to_price($a_amount, $b_amount);
        $this_orderid = $row['orderid'];
        $timest = $row['timest'];
        $give_precision = $type == 'BTC' ? BTC_PRECISION : FIAT_PRECISION;
        $want_precision = $type == 'BTC' ? FIAT_PRECISION : BTC_PRECISION;
        if (!$orderid)
            echo "    ", active_table_row("active", "?page=view_order&orderid=$this_orderid"), "\n";
        else
            echo "    <tr>\n";
        echo "        ";
        if (!$orderid)
            echo "<td class='right'>$this_orderid</td>";
        echo "<td class='right'>" . internal_to_numstr($a_amount, $give_precision) . " $type</td>";
        echo "<td class='right'>" . internal_to_numstr($b_amount, $want_precision) . " $want_type</td>";
        echo "<td class='right'>" . internal_to_numstr($b_commission, $want_precision) . " $want_type (", sprintf("%.2f", $commission_percent), "%)</td>";
        echo "<td class='right'>$price</td>";
        echo "<td class='right'>$timest</td>\n";
        echo "    </tr>\n";
    }

    // if we showed any table at all
    if (!$first) {
        // if we need to show a summary line
        if ($orderid && $count > 1) {
            $commission_percent = bcdiv(bcmul(gmp_strval($commission_total), 100), gmp_strval($b_total), 3);

            $b_total = gmp_sub($b_total, $commission_total);

            $price = 0;
            if ($type == 'BTC') {
                if ($a_total) $price = fiat_and_btc_to_price($b_total, $a_total);
            } else
                if ($b_total) $price = fiat_and_btc_to_price($a_total, $b_total);

            $a_total = internal_to_numstr($a_total, $give_precision);
            $b_total = internal_to_numstr($b_total, $want_precision);
            $commission_total = internal_to_numstr($commission_total, $want_precision);

            echo "    <tr>\n";
            echo "        <td class='right'>--------</td><td class='right'>--------</td><td class='right'>--------</td><td class='right'>--------</td>\n";
            echo "        <td></td>\n";
            echo "    </tr>\n";
            echo "    <tr>\n";
            echo "        <td class='right'>$a_total $type</td><td class='right'>$b_total $want_type</td><td class='right'>$commission_total $want_type (",
                sprintf("%.2f", $commission_percent), "%)</td><td class='right'>$price</td>\n";
            echo "        <td></td>\n";
            echo "    </tr>\n";
        }

        echo "</table>\n";
        echo "<p>" . _("The 'you got' column is the amount you received after commission was taken off.") . "</p>";
        echo "<p>" . _("The 'price' column shows the effective price of the trade, after commission.") . "</p>";
        echo "</div>\n";
    }
}

?>
