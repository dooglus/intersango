SELECT *, b_want/b_amount AS b_rate, a_amount/a_want AS a_rate
FROM (
    SELECT txid, a_amount, a_want, initial_amount AS b_amount, initial_want_amount AS b_want
    FROM (
        SELECT txid, initial_amount AS a_amount, initial_want_amount AS a_want, b_orderid
        FROM transactions
        JOIN orderbook
        ON transactions.a_orderid=orderbook.orderid
        ) AS a
    JOIN orderbook
    ON b_orderid=orderbook.orderid
    ) AS j
HAVING b_rate > a_rate;
