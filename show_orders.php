<div class='content_box'>
<h3>People offering BTC for GBP</h3>
<?php
require 'db.php';
$query = "SELECT * FROM orderbook WHERE type='BTC' AND want_type='GBP';";
$result = do_query($query);
?>

<table class='display_data'>
    <tr>
        <th>Giving</th>
        <th>Wanted</th>
        <th>Price per BTC</th>
    </tr>
<?php
while($row = mysql_fetch_array($result)) {
    $amount = internal_to_numstr($row['amount']);
    $want_amount = internal_to_numstr($row['want_amount']);
    $amount = gmp_strval($amount);
    $want_amount = gmp_strval($want_amount);
    #$exchange_rate = gmp_div
    echo "    <tr>\n";
    echo "        <td>$amount BTC</td>\n";
    echo "        <td>$want_amount GBP</td>\n";
    echo "    </tr>\n";
}
?>
</table>
</div>
