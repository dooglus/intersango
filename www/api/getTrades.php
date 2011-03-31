<?php
require "../config.php";
require "$abspath/util.php";

echo '[';
$query = "
    SELECT
        UNIX_TIMESTAMP(transactions.timest) AS timest,
        IF(
            type='BTC',
            b_amount/a_amount,
            a_amount/b_amount
        ) AS rate,
        IF(
            type='BTC',
            a_amount,
            b_amount
        ) AS amount
    FROM
        transactions
    JOIN
        orderbook
    ON
        transactions.a_orderid=orderbook.orderid
    WHERE
        b_amount >= 0
    ";
$result = do_query($query);
$first = true;
while ($row = mysql_fetch_assoc($result)) {
    if ($first)
        $first = false;
    else
        echo ', ';
    echo '{"date": ';
    echo $row['timest'];
    echo ', "price": ';
    echo $row['rate'];
    echo ', "amount": ';
    echo internal_to_numstr($row['amount']);
    echo '}';
}
echo ']';
?>

