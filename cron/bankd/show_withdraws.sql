UPDATE requests
SET status='PROCES'
WHERE
    req_type='WITHDR'
    AND status='VERIFY'
    AND curr_type='GBP';

SELECT
    amount/100000000 AS amount,
    name,
    bank,
    acc_num,
    sort_code
FROM
    requests
JOIN
    uk_requests
ON
    uk_requests.reqid=requests.reqid
WHERE
    req_type='WITHDR'
    AND status='PROCES'
    AND curr_type='GBP';

UPDATE requests
SET status='FINAL'
WHERE
    req_type='WITHDR'
    AND status='PROCES'
    AND curr_type='GBP';
