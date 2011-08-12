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

try {
    check_frozen();

    // find and cancel any active requests from users with negative BTC or AUD balances
    // this should never happen unless someone is trying to double-spend their balance
    $query = "
    SELECT
        reqid, requests.amount as amount, requests.uid as uid
    FROM requests
    JOIN purses
    ON requests.uid = purses.uid
    WHERE
        req_type = 'WITHDR'
        AND curr_type = 'BTC'
        AND (status = 'VERIFY' OR status = 'PROCES')
        AND purses.amount < 0
    GROUP BY reqid
";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $reqid = $row['reqid'];
        $amount = $row['amount'];
        $uid = $row['uid'];
        try {
            echo "cancelling reqid $reqid (withdraw ", internal_to_numstr($amount), " BTC for user $uid) due to negative balance\n";
            $lock = get_lock($uid);
            $query = "
    UPDATE requests
    SET status = 'CANCEL'
    WHERE reqid = '$reqid'
        ";
            do_query($query);
            add_funds($uid, $amount, 'BTC');
            release_lock($lock);
        }
        catch (Error $e) {
            if ($e->getTitle() == 'Lock Error')
                echo "can't get lock for $uid\n";
            else
                throw $e;
        }
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
}
catch (Error $e) {
    report_exception($e, SEVERITY::ERROR);
    // Same as below, but flag + log this for review,
    echo "\nError: \"{$e->getTitle()}\"\n  {$e->getMessage()}\n";
}
catch (Problem $e) {
    echo "\nProblem: \"{$e->getTitle()}\"\n  {$e->getMessage()}\n";
}
catch (Exception $e) {
    echo "\nException: \"{$e->getTitle()}\"\n  {$e->getMessage()}\n";
}
?>
