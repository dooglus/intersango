<?php
require_once '../htdocs/config.php';
require_once '../util.php';

$is_logged_in = 'verify_withdrawals_bitcoin';

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

    // find and cancel any active requests from users with negative BTC or FIAT balances
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
            wait_for_lock($uid);
            $query = "
    UPDATE requests
    SET status = 'CANCEL'
    WHERE reqid = '$reqid'
        ";
            do_query($query);
            add_funds($uid, $amount, 'BTC');
            release_lock($uid);
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
        users.uid AS uid,
        amount,
        addy
    FROM requests
    JOIN bitcoin_requests
    ON requests.reqid=bitcoin_requests.reqid
    JOIN users
    ON users.uid=requests.uid
    WHERE
        req_type='WITHDR'
        AND amount > 1000000
        AND status='VERIFY'
        AND curr_type='BTC'
        AND (users.uid < " . LOWEST_UNTRUSTED_USERID . " OR verified)
    ";
    $result = do_query($query);
    while ($row = mysql_fetch_assoc($result)) {
        $reqid = $row['reqid'];
        $uid = $row['uid'];
        $amount = $row['amount'];
        $addy = $row['addy'];
        $we_have = bitcoin_get_balance("*", CONFIRMATIONS_FOR_DEPOSIT);

        addlog(LOG_CRONJOB, "Attempting to withdraw " . internal_to_numstr($amount) .
               " of " . internal_to_numstr($we_have) . " BTC for user $uid (reqid $reqid)");

        if (gmp_cmp($we_have, $amount) >= 0) {
            update_req($reqid, "PROCES");

            // use 'sendtoaddress' rather than 'sendfrom' because it can 'go overdrawn'
            // so long as there are funds in other accounts (pending deposits) to cover it
            bitcoin_send_to_address($addy, $amount);
            update_req($reqid, "FINAL");

            $we_have = bitcoin_get_balance("*", 0);
            addlog(LOG_CRONJOB, "We have " . internal_to_numstr($we_have) . " BTC in total");
            if (gmp_cmp($we_have, numstr_to_internal(WARN_LOW_WALLET_THRESHOLD)) < 0)
                email_tech(_("Exchange Wallet Balance is Low"),
                           sprintf(_("The exchange wallet only has %s BTC available."),
                                   internal_to_numstr($we_have, BTC_PRECISION)));
        } else {
            $message = sprintf(_("We only have %s BTC so can't withdraw %s BTC"),
                               internal_to_numstr($we_have, BTC_PRECISION),
                               internal_to_numstr($amount, BTC_PRECISION));
            addlog(LOG_CRONJOB, $message);
            // email_tech(_("Exchange Wallet Balance is Too Low"), $message);
        }
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
