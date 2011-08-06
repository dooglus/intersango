<?php
require_once "../config.php";
require_once "$abspath/util.php";

echo '{"ticker": {';
$query = "
    SELECT
        SUM(initial_amount - amount) AS vol
    FROM
        orderbook
    WHERE
        type='BTC'
        AND timest BETWEEN NOW() - INTERVAL 1 DAY AND NOW()
    ";
$result = do_query($query);
$row = get_row($result);
if (isset($row['vol']))
    $vol = internal_to_numstr($row['vol']);
else
    $vol = 0;
echo '"vol": ' . $vol . ', ';

$exchange_fields = calc_exchange_rate('AUD', 'BTC', BASE_CURRENCY::B);
if (!$exchange_fields)
    $rate = 0;
else
    list($total_amount, $total_want_amount, $rate) = $exchange_fields; 
echo '"buy": ' . $rate . ', ';

$exchange_fields = calc_exchange_rate('BTC', 'AUD', BASE_CURRENCY::A);
if (!$exchange_fields)
    $rate = 0;
else
    list($total_amount, $total_want_amount, $rate) = $exchange_fields; 
echo '"sell": ' . $rate . ', ';

$query = "
    SELECT
        a_amount,
        ord_a.type AS a_type,
        b_amount,
        ord_b.type AS b_type
    FROM
        transactions AS t
    JOIN
        orderbook AS ord_a
    ON
        ord_a.orderid=a_orderid
    JOIN
        orderbook AS ord_b
    ON
        ord_b.orderid=b_orderid
    WHERE
        b_amount >= 0
    ORDER BY
        t.timest DESC
    LIMIT 1
    ";
$result = do_query($query);
if (has_results($result)) {
    $row = get_row($result);
    $a_amount = $row['a_amount'];
    $a_type = $row['a_type'];
    $b_amount = $row['b_amount'];
    $b_type = $row['b_type'];
    if ($a_type == 'AUD') {
        # swap them around so BTC is always the base currency
        list($a_amount, $b_amount) = array($b_amount, $a_amount);
        list($a_type, $b_type) = array($b_type, $a_type);
    }
    if ($a_type == 'BTC' && $b_type == 'AUD')
        $rate = (float)$b_amount / (float)$a_amount;
    else
        $rate = 0;
    echo '"last": ' . $rate . '}}';
}
else
    echo '"last": 0}}';
?>

