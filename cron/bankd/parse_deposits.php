<?php
require '../../util.php';

function b_query($query)
{
    #echo $query;
    do_query($query);
}

$query = "
    SELECT
        bid, entry
    FROM
        bank_statement
    WHERE
        reqid IS NULL
        AND status='VERIFY'
    ";
$result = do_query($query);

while ($row = mysql_fetch_array($result)) {
    $bid = $row[0];
    $query = "
        UPDATE
            bank_statement
        SET
            status='PROC'
        WHERE
            bid='$bid'
            AND status='VERIFY'
        ";
    b_query($query);
    $line = $row[1];
    print "$line\n";
    $info = split(',', $line);
    if ($info[5] != '') {
        echo "Skipping payment out...\n";
        continue;
    }
    $acc = split('[.]', $info[4]);
    if (count($acc) < 2) {
        echo "\nProblem processing {$info[4]}...\n\n";
        exit(-1);
    }
    $deposref = trim($acc[1], " \"'\n");
    $amount = $info[6];
    $amount = numstr_to_internal($amount);
    print "$deposref <= $amount\n";

    $query = "
        INSERT INTO requests (
            req_type,
            uid,
            amount,
            curr_type
        ) SELECT
            'DEPOS',
            uid,
            '$amount',
            'GBP'
        FROM users
        WHERE
            deposref='$deposref';
        ";
    b_query($query);
    $query = "
        UPDATE
            bank_statement
        SET
            reqid=LAST_INSERT_ID()
            AND status='FINAL'
        WHERE
            bid='$bid'
        ";
    b_query($query);
}

