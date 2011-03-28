UPDATE
    requests
JOIN
    purses
ON
    purses.uid=requests.uid
    AND purses.type=requests.curr_type
SET
    requests.status='FINAL',
    purses.amount = purses.amount + requests.amount
WHERE
    requests.status='VERIFY'
    AND requests.req_type='DEPOS'
    AND (
        requests.curr_type='BTC'
        OR requests.timest < NOW() - INTERVAL 1 DAY
        )
;
