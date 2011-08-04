<?php
require_once '../htdocs/config.php';
require_once '../util.php';

function update_req($reqid, $status)
{
    $query = "
        UPDATE requests
        SET status='$status'
        WHERE
            reqid='$reqid'
            AND curr_type='BTC'
        ";
    do_query($query);
}

$query = "
    SELECT
        requests.reqid AS reqid,
        uid,
        amount,
        addy
    FROM requests
    JOIN bitcoin_requests
    ON requests.reqid=bitcoin_requests.reqid
    WHERE
        req_type='WITHDR'
        AND amount > 1000000
        AND status='VERIFY'
        AND curr_type='BTC'
    ";
$result = do_query($query);
$bitcoin = connect_bitcoin();
while ($row = mysql_fetch_assoc($result)) {
    $reqid = $row['reqid'];
    $uid = $row['uid'];
    $amount = $row['amount'];
    $addy = $row['addy'];
    $we_have = $bitcoin->getbalance("", confirmations_for_deposit());

    if (gmp_cmp($we_have, $amount) >= 0)
    {
        update_req($reqid, "PROCES");
        $bitcoin->sendfrom("", $addy, $amount);
        update_req($reqid, "FINAL");
    }
    else
        echo "we only have ", internal_to_numstr($we_have), " BTC so can't withdraw ", internal_to_numstr($amount), " BTC\n";
}

?>
