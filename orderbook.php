<?php
require_once 'util.php';

function display_double_entry($curr_a, $curr_b, $base_curr, $uid, $is_admin)
{
    if (isset($_GET['show_all']) && get('show_all') == 'true')
        $show_all = true;
    else 
        $show_all = false;
    
    echo "<div class='content_box'>\n";
    if ($curr_a == 'BTC')
        echo "<h3>" . sprintf(_("People selling %s for %s"), $curr_a, $curr_b) . "</h3>\n";
    else
        echo "<h3>" . sprintf(_("People buying %s for %s"), $curr_b, $curr_a) . "</h3>\n";

    $exchange_fields = calc_exchange_rate($curr_a, $curr_b, $base_curr);        
    if (!$exchange_fields) {
        if ($curr_a == 'BTC')
            echo "<p>" . sprintf(_("Nobody is selling %s for %s."), $curr_a, $curr_b) . "</p>";
        else
            echo "<p>" . sprintf(_("Nobody is buying %s for %s."), $curr_b, $curr_a) . "</p>";
        echo "</div>";
        return;
    }
    list($total_amount, $total_want_amount, $rate) = $exchange_fields; 
    echo "<p>" . _("Best exchange rate is") . " ";
    if ($base_curr == BASE_CURRENCY::A)
        echo "<b>$rate $curr_b/$curr_a</b>";
    else
        echo "<b>$rate $curr_a/$curr_b</b>";
    echo ".</p>";

    if (!$show_all)
        echo "<p>" . _("Showing top 5 entries") . ":</p>";

?><table class='display_data'>
        <tr>
            <th><?php echo _("Cost / BTC"); ?></th>
            <th><?php echo _("Giving"); ?></th>
            <th><?php echo _("Wanted"); ?></th>
<?php if ($is_admin) { ?>
            <th><?php echo _("User"); ?></th>
<?php } ?>
        </tr><?php

    $show_query = 'LIMIT 5';
    if ($show_all)
        $show_query = '';

    $query = "
        SELECT
            orderid,
            amount,
            want_amount,
            uid=$uid as me,
            uid,
            IF(
                type='BTC',
                initial_want_amount/initial_amount,
                initial_amount/initial_want_amount
            ) AS rate
        FROM orderbook
        WHERE type='$curr_a' AND want_type='$curr_b' AND status='OPEN'
        ORDER BY
            IF(type='BTC', rate, -rate) ASC, timest ASC
        $show_query
    ";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $amount_i = $row['amount'];
        $amount = internal_to_numstr($amount_i);
        $want_amount_i = $row['want_amount'];
        $want_amount = internal_to_numstr($want_amount_i);
        # MySQL kindly computes this for us.
        # we trim the excessive 0
        $rate = clean_sql_numstr($row['rate']);
        $me = $row['me'];
        $uid = $row['uid'];
        if ($me)
            echo "    ", active_table_row("me", "?page=view_order&orderid={$row['orderid']}");
        else
            echo "    ", active_table_row("them", "?page=trade&in=$curr_b&have=$want_amount_i&want=$amount_i&rate=$rate");
        echo "        <td>$rate</td>\n";
        echo "        <td>$amount $curr_a</td>\n";
        echo "        <td>$want_amount $curr_b</td>\n";
        if ($is_admin)
            echo "        <td>$uid</td>\n";
        echo "    </tr>\n";
    }

    echo "    <tr>\n";
    echo "        <td>" . _("Total") . ":</td>\n";
    # strstr's 3rd argument only works in PHP 5.3.0 and newer
    #   http://php.net/manual/en/function.strstr.php
    # use explode instead
    $total_amount = explode('.', $total_amount, 2);
    $total_amount = $total_amount[0];
    echo "        <td>$total_amount $curr_a</td>\n";
    echo "        <td></td>\n";
    echo "    </tr>\n";
    echo "</table>\n";
    if ($show_all)
        echo "<p><a href='?page=orderbook&show_all=false'>&gt;&gt; " . _("hide") . "</a></p>\n";
    else
        echo "<p><a href='?page=orderbook&show_all=true'>&gt;&gt; " . _("show all") . "</a></p>\n";
    echo "</div>\n";
}

global $is_logged_in, $is_admin;

display_double_entry('BTC', CURRENCY, BASE_CURRENCY::A, $is_logged_in, $is_admin);
display_double_entry(CURRENCY, 'BTC', BASE_CURRENCY::B, $is_logged_in, $is_admin);
?>
