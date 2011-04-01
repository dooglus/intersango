<?php
require "../config.php";
require "$abspath/util.php";

$query = "
    SELECT
        initial_want_amount / initial_amount AS rate,
        amount
    FROM
        orderbook
    WHERE
        type='BTC'
        AND want_type='GBP'
        AND status='OPEN'
    ";
$result = do_query($query);
$first = true;
echo '{"asks": [';
while ($row = mysql_fetch_assoc($result)) {
    if ($first)
        $first = false;
    else
        echo ", ";
    $rate = $row['rate'];
    $amount = internal_to_numstr($row['amount']);
    echo "[$rate, $amount]";
}
echo '], "bids": [';
$query = "
    SELECT
        initial_amount / initial_want_amount AS rate,
        amount
    FROM
        orderbook
    WHERE
        type='GBP'
        AND want_type='BTC'
        AND status='OPEN'
    ";
$result = do_query($query);
$first = true;
while ($row = mysql_fetch_assoc($result)) {
    if ($first)
        $first = false;
    else
        echo ", ";
    $rate = $row['rate'];
    $amount = internal_to_numstr($row['amount']);
    echo "[$rate, $amount]";
}
echo ']}';

?>

