<?php
require '../../util.php';

if (count($argv) < 3) {
    echo "fix_badref [bank statement ID (bid)] [deposref]\n";
    exit(-1);
}

$bid = $argv[1];
$deposref = $argv[2];

$query = "
    SELECT entry
    FROM bank_statement
    WHERE
        bid='$bid'
        AND reqid IS NULL
        AND status='BADREF'
    ";
$result = do_query($query);
if (!has_results($result)) {
    echo "No results found for bank statement $bid...\n";
    exit(-1);
}
$row = get_row($result);
$entry = $row['entry'];
$entry = split(',', $entry);
$amount = $entry[6];
$amount = numstr_to_internal($amount);

$query = "
    SELECT uid
    FROM users
    WHERE deposref='$deposref'
    ";
$result = do_query($query);
if (!has_results($result)) {
    echo "No user found with deposref of $deposref...\n";
    exit(-1);
}
$row = get_row($result);
$uid = $row['uid'];

$query = "
    INSERT INTO requests (
        req_type,
        uid,
        amount,
        curr_type
    ) VALUES (
        'DEPOS',
        $uid,
        $amount,
        'AUD'
    )";
do_query($query);
$query = "
    UPDATE bank_statement
    SET
        reqid=LAST_INSERT_ID(),
        status='FINAL'
    WHERE
        bid='$bid'
        AND reqid is NULL
        AND status='BADREF'
    ";
do_query($query);
echo "Done.\n";

