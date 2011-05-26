<?php
require '../../util.php';

function b_query($query)
{
    #echo $query;
    do_query($query);
}

function deposref_exists($deposref)
{
    $query = "
        SELECT 1
        FROM
            users
        WHERE
            deposref='$deposref'
        LIMIT 1
        ";
    $result = do_query($query);
    return has_results($result);
}

$query = "
    SELECT
        bid, entry
    FROM
        bank_statement
    WHERE
        reqid IS NULL
        AND status='PROC'
    ";
$result = do_query($query);

while ($row = mysql_fetch_array($result)) {
    $bid = $row[0];
    $line = $row[1];
    print "$line\n";
    $info = split(',', $line);
    if ($info[5] == '') {
        echo "Skipping payment in...\n";
        continue;
    }
    $name = $info[4];
    $name = trim($name, ' "');
    # trim off the BRITCOIN
    $lastdot = strrpos($name, ' . ');
    $name = substr($name, 0, $lastdot);
    print_r($name);
    $amount = $info[5];
    echo "We paid $amount to $name.\n";
    $amount = numstr_to_internal($amount);

    $query = "
        SELECT
            requests.reqid AS reqid
        FROM
            requests
        JOIN
            uk_requests
        ON
            requests.reqid=uk_requests.reqid
        WHERE
            requests.req_type='WITHDR'
            AND requests.curr_type='GBP'
            AND requests.amount='$amount'
            AND uk_requests.name='$name'
        ";
    $result_lookup = do_query($query);
    if (!has_results($result_lookup)) {
        echo "ERROR: could not find this withdrawal!\n";
        break;
    }
    $row = get_row($result_lookup);
    $reqid = $row['reqid'];
    echo "reqid is $reqid\n\n";

    # now check that this request doesn't already have an entry
    $query = "
        SELECT 1
        FROM bank_statement
        WHERE reqid='$reqid'
        ";
    $result_lookup = do_query($query);
    if (has_results($result_lookup)) {
        echo "ERROR: this request id already has an entry in bank_statement.";
        break;
    }

    $query = "
        UPDATE
            bank_statement
        SET
            reqid='$reqid',
            status='FINAL'
        WHERE
            bid='$bid'
        ";
    b_query($query);
}

