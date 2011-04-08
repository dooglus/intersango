SELECT entry, amount, req_type, requests.timest
FROM bank_statement
LEFT JOIN requests
ON requests.reqid=bank_statement.reqid
;
