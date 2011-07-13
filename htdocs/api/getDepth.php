<?php
require_once "../config.php";
require_once ABSPATH . "/util.php";

$query = "
    SELECT
        initial_want_amount / initial_amount AS rate,
        amount
    FROM
        orderbook
    WHERE
        type='BTC'
        AND want_type='AUD'
        AND status='OPEN'
    ";
$result = do_query($query);
$first = true;
echo '{"asks": [';
while ($row = mysql_fetch_assoc($result)) {
    $amount = internal_to_numstr($row['amount']);
    $rate = $row['rate'];
    
    //bitcoincharts uses NUMERIC(18,8)
    if($rate < 1000000000)
    {
        if ($first)
            $first = false;
        else
            echo ", ";
        echo "[$rate, $amount]";
    }
}
echo '], "bids": [';

// find exchange rate
$query = "
    SELECT
        MIN(initial_want_amount / initial_amount) AS rate,
        amount
    FROM
        orderbook
    WHERE
        type='BTC'
        AND want_type='AUD'
        AND status='OPEN'
    ";
$result = do_query($query);
$row = get_row($result);
$best_rate = $row['rate'];

$query = "
    SELECT
        initial_amount / initial_want_amount AS rate,
        ROUND (
            amount / $best_rate,
            0
        ) AS amount
    FROM                        
        orderbook
    WHERE
        type='AUD'
        AND want_type='BTC'
        AND status='OPEN'
    ";
$result = do_query($query);
$first = true;
while ($row = mysql_fetch_assoc($result)) {
    $amount = clean_sql_numstr($row['amount']);
    $amount = internal_to_numstr($amount);
    
    $rate = $row['rate'];
    
    //bitcoincharts uses NUMERIC(18,8)
    if($rate < 1000000000)
    {
        if ($first)
            $first = false;
        else
            echo ", ";
        echo "[$rate, $amount]";
    }
}
echo ']}';

?>

