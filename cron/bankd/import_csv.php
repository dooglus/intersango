<?php
require '../../util.php';

if (count($argv) < 3) 
{
    echo "php import_csv.php [bank_name] [CSV]\n";
    exit(-1);
}
$bank_name = $argv[1];
$lines = file($argv[2], FILE_IGNORE_NEW_LINES);

if ($bank_name != 'LloydsTSB' && $bank_name != 'HSBC')
{
    echo "Incorrect bank specified.\n";
    exit(-1);
}

foreach ($lines as $line_num => $line) {
    $line = mysql_real_escape_string($line);
    $query = "
        INSERT IGNORE INTO
            bank_statement (bank_name, entry)
        VALUES (
            '$bank_name',
            '$line'
        )
        ";
    do_query($query);
}

