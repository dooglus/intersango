<?php
require 'db.php';

function display_double_entry($curr_a, $curr_b)
{
    
    echo "<div class='content_box'>\n";
    echo "<h3>People offering $curr_a for $curr_b</h3>\n";

    $exchange_fields = calc_exchange_rate($curr_a, $curr_b);        
    if (!$exchange_fields) {
        echo "<p>Nobody is selling $curr_a for $curr_b.</p>";
        echo "</div>";
        return;
    }
    list($total_amount, $total_want_amount, $rate) = $exchange_fields; 
    echo "<p>Total weighted exchange rate is <b>$rate $curr_b per $curr_a</b>.</p>";
    echo "<p>$total_amount $curr_a being sold for $total_want_amount $curr_b.</p>";


?><table class='display_data'>
        <tr>
            <th>Rate / <?php echo $curr_a; ?></th>
            <th>Giving</th>
            <th>Wanted</th>
        </tr><?php

    $query = "SELECT *, amount/want_amount AS rate FROM orderbook WHERE type='$curr_a' AND want_type='$curr_b';";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $amount = internal_to_numstr($row['amount']);
        $want_amount = internal_to_numstr($row['want_amount']);
        # MySQL kindly computes this for us.
        # we trim the excessive 0
        $rate = clean_sql_numstr($row['rate']);
        echo "    <tr>\n";
        echo "        <td>$rate</td>\n";
        echo "        <td>$amount $curr_a</td>\n";
        echo "        <td>$want_amount $curr_b</td>\n";
        echo "    </tr>\n";
    }
    echo "    <tr>\n";
    echo "        <td>Total:</td>\n";
    echo "        <td>$total_amount $curr_a</td>\n";
    echo "        <td>$total_want_amount $curr_b</td>\n";
    echo "    </tr>\n";
    echo "</table></div>";
}
display_double_entry('BTC', 'GBP');
display_double_entry('GBP', 'BTC');
?>

