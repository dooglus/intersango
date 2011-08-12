<?php
require_once '../util.php';

try {
    check_frozen();

    $query = "
    SELECT
        reqid,
        uid,
        curr_type,
        amount
    FROM
        requests
    WHERE
        status='VERIFY'
        AND req_type='DEPOS'
    ";
    $result = do_query($query);

    while ($row = mysql_fetch_assoc($result))
    {
        $reqid = $row['reqid'];
        $query = "
        UPDATE
            requests
        SET
            status='PROCES'
        WHERE
            reqid='$reqid'
        ";
        do_query($query);

        $uid = $row['uid'];
        $type = $row['curr_type'];
        $amount = $row['amount'];

        $query = "
        UPDATE
            purses
        SET
            amount=amount+'$amount'
        WHERE
            uid='$uid'
            AND type='$type'
        ";
        do_query($query);

        $query = "
        UPDATE
            requests
        SET
            status='FINAL'
        WHERE
            reqid='$reqid'
        ";
        do_query($query);
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
