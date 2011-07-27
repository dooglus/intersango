SELECT
    txid,
    b_r AS b_rate,
    a_r AS a_rate,
    r AS rate,
    b_r > a_r,
    r > a_r,
    r < b_r
FROM (
    SELECT
        txid,
        b_want/b_amount AS b_r,
        a_amount/a_want AS a_r,
        a_exc/b_exc AS r
    FROM (
        SELECT
            txid,
            a_amount,
            a_want,
            initial_amount AS b_amount,
            initial_want_amount AS b_want,
            a_exc,
            b_exc
        FROM (
            SELECT
                t.txid,
                o.initial_amount AS a_amount,
                o.initial_want_amount AS a_want,
                t.b_orderid,
                t.a_amount AS a_exc,
                t.b_amount AS b_exc
            FROM
                transactions AS t
            JOIN
                orderbook AS o
            ON
                t.a_orderid=o.orderid
            WHERE
                b_amount >= 0
            ) AS a
        JOIN
            orderbook
        ON
            b_orderid=orderbook.orderid
        ) AS j
    ) AS t
WHERE
    b_r > a_r
    OR r > a_r
    OR r < b_r
    OR TRUE
;

SELECT
    SUM(
        IF(
            req_type='DEPOS',
            amount,
            IF(
                req_type='WITHDR',
                -amount,
                0
            ))) AS total_AUD_deposits
FROM
    requests
WHERE
    curr_type='AUD'
    AND status='FINAL'
;
