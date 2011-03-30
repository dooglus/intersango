<?php
require '../../util.php';
if (count($argv) < 2) {
    echo "Need bank CSV\n";
    exit(-1);
}
$lines = file($argv[1], FILE_IGNORE_NEW_LINES);

foreach ($lines as $line_num => $line) {
    $info = split(',', $line);
    $acc = split('[.]', $info[4]);
    $deposref = trim($acc[1]);
    $amount = $info[6];
    $amount = numstr_to_internal($amount);
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
    echo $query;
    #do_query($query);
}

