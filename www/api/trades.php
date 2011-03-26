<?php
require "../config.php";
require "$abspath/util.php";

echo '{"ticker": {';
$query = "SELECT SUM(amount) AS vol FROM orderbook WHERE type='BTC' AND status='OPEN'";
$result = do_query($query);
$row = get_row($result);
if (isset($row['vol']))
    $vol = internal_to_numstr($row['vol']);
else
    $vol = 0;
echo '"vol": ' . $vol . ', ';

$exchange_fields = calc_exchange_rate('GBP', 'BTC', BASE_CURRENCY::B);
if (!$exchange_fields)
    $rate = 0;
else
    list($total_amount, $total_want_amount, $rate) = $exchange_fields; 
echo '"buy": ' . $rate . ', ';

$exchange_fields = calc_exchange_rate('BTC', 'GBP', BASE_CURRENCY::A);
if (!$exchange_fields)
    $rate = 0;
else
    list($total_amount, $total_want_amount, $rate) = $exchange_fields; 
echo '"sell": ' . $rate . ', ';

$query = "
    SELECT
        initial_amount,
        initial_want_amount,
        type,
        want_type
    FROM
        orderbook
    ORDER BY
        timest DESC
    LIMIT 1
    ";
$result = do_query($query);
if (has_results($result)) {
    $row = get_row($result);
    $have = $row['initial_amount'];
    $want = $row['initial_want_amount'];
    $type = $row['type'];
    $want_type = $row['want_type'];
    if ($type == 'BTC' && $want_type == 'GBP') {
        $rate = (float)$want / (float)$have;
    }
    else if ($type == 'GBP' && $want_type == 'BTC') {
        $rate = (float)$have / (float)$want;
    }
    else
        $rate = 0;
    echo '"last": ' . $rate . '}}';
}
else
    echo '"last": 0}}';
?>

