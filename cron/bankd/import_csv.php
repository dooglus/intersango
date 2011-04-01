<?php
require '../../util.php';
if (count($argv) < 2) {
    echo "Need bank CSV\n";
    exit(-1);
}
$lines = file($argv[1], FILE_IGNORE_NEW_LINES);

foreach ($lines as $line_num => $line) {
    $line = mysql_real_escape_string($line);
    $query = "
        INSERT IGNORE INTO
            bank_statement (entry)
        VALUES (
            '$line'
        )
        ";
    do_query($query);
}
?>

