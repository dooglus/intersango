<?php
require 'db.php';

function display_double_entry($curr_a, $curr_b)
{
    
    echo "<div class='content_box'>\n";
    echo "<h3>People offering $curr_a for $curr_b</h3>\n";
    # we will use the data from this query down below, but do this first so that we know whether any transactions exist,
    $query = "SELECT *, amount/want_amount AS rate FROM orderbook WHERE type='$curr_a' AND want_type='$curr_b';";
    $result = do_query($query);
    if (!has_results($result)) {
        echo "<p>Nobody is selling $curr_a for $curr_b.</p>";
        echo "</div>";
        return;
    }

    $query = "SELECT total_amount, total_wanted, total_wanted/total_amount AS rate FROM (SELECT SUM(amount) AS total_amount, SUM(want_amount) as total_wanted FROM orderbook WHERE type='$curr_a' AND want_type='$curr_b') AS tbl;";
    $total_result = do_query($query);
    $row = mysql_fetch_array($total_result);
    $total_amount = internal_to_numstr($row['total_amount']);
    $total_want_amount = internal_to_numstr($row['total_wanted']);
    $rate = clean_sql_numstr($row['rate']);
    echo "<p>Total weighted exchange rate is <b>$rate $curr_b per $curr_a</b>.</p>";
    echo "<p>$total_amount $curr_a being sold for $total_want_amount $curr_b.</p>";


?><table class='display_data'>
        <tr>
            <th>Rate / <?php echo $curr_a; ?></th>
            <th>Giving</th>
            <th>Wanted</th>
        </tr><?php

    while ($row = mysql_fetch_array($result)) {
        $amount = internal_to_numstr($row['amount']);
        $want_amount = internal_to_numstr($row['want_amount']);
        $amount = gmp_strval($amount);
        $want_amount = gmp_strval($want_amount);
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

