<?php
require '../util.php';

function summa($type)
{
    $query = "
        SELECT SUM(amount) AS sum
        FROM purses
        WHERE type='$type'
        ";
    $result = do_query($query);
    $row = get_row($result);
    $v = $row['sum'];

    $query = "
        SELECT SUM(amount) AS sum
        FROM orderbook
        WHERE type='$type' AND status='OPEN'
        ";
    $result = do_query($query);
    $row = get_row($result);
    $v += $row['sum'];

    $query = "
        SELECT SUM(amount) AS sum
        FROM requests
        WHERE curr_type='$type' AND req_type='WITHDR' AND status='VERIFY'
        ";
    $result = do_query($query);
    $row = get_row($result);
    $v += $row['sum'];

    $query = "
        SELECT SUM(amount) AS sum
        FROM requests
        WHERE curr_type='$type' AND req_type='DEPOS' AND status='FINAL'
        ";
    $result = do_query($query);
    $row = get_row($result);
    $u = $row['sum'];
    echo "$type = $v\t  $u\n";
    if ($u != $v)
        echo "*********** MISMATCH ****************\n";
}

summa('BTC');    
summa('GBP');    
?>

