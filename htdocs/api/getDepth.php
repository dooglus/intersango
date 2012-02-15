<?php
require_once "../config.php";
require_once ABSPATH . "/util.php";

log_api('getDepth');

function fetch_depth($rate_query, $field, $have, $want)
{
    $ret = array();

    $query = "
    SELECT
        $rate_query AS rate,
        $field as amount
    FROM
        orderbook
    WHERE
        type='$have'
        AND want_type='$want'
        AND status='OPEN'
    ORDER BY
        rate DESC
    ";
    $result = do_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $amount = internal_to_numstr($row['amount']);
        $rate = $row['rate'];

        //bitcoincharts uses NUMERIC(18,8)
        if ($rate < 1000000000)
            array_push($ret, "[$rate, $amount]");
    }

    return implode($ret, ", ");
}

printf('{"asks": [%s], "bids": [%s]}',
       fetch_depth("initial_want_amount / initial_amount",      "amount", "BTC", CURRENCY),
       fetch_depth("initial_amount / initial_want_amount", "want_amount", CURRENCY, "BTC"));
?>
