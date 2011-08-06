<?php
require_once '../../util.php';

if (count($argv) < 3) {
    echo "reject_withdrawal [request ID (reqid)] [person name]\n";
    exit(-1);
}

$reqid = $argv[1];
$name = $argv[2];

# check if this name matches for that request ID
$query = "
    SELECT 
        amount,
        uid
    FROM requests
    JOIN uk_requests
    ON requests.reqid=uk_requests.reqid
    WHERE
        requests.reqid='$reqid'
        AND uk_requests.name='$name'
        AND ( requests.status='PROCES' OR requests.status='FINAL' )
    ";
$result = do_query($query);
if (!has_results($result)) {
    echo "No results found for this request $reqid...\n";
    exit(-1);
}
$row = get_row($result);
$amount = $row['amount'];
$uid = $row['uid'];
echo "Adding $amount to $name ($uid)...\n";

do_query("START TRANSACTION");
$query = "
    UPDATE requests
    SET status='REJECT'
    WHERE
        reqid='$reqid'
        AND ( status='PROCES' OR status='FINAL' )
    ";
do_query($query);
$query = "
    UPDATE purses
    SET amount=amount+'$amount'
    WHERE
        uid='$uid'
        AND type='AUD'
    ";
do_query($query);
do_query("COMMIT");

