<?php
require '../util.php';

function summa($type)
{
    $total_in = gmp_init('0');
    $query = "
        SELECT SUM(amount) AS sum
        FROM purses
        WHERE type='$type'
        ";
    $result = do_query($query);
    $row = get_row($result);
    $v = gmp_init($row['sum']);
    $total_in = gmp_add($total_in, $v);

    $query = "
        SELECT SUM(amount) AS sum
        FROM orderbook
        WHERE type='$type' AND status='OPEN'
        ";
    $result = do_query($query);
    $row = get_row($result);
    if (isset($row['sum'])) {
        $v = gmp_init($row['sum']);
        $total_in = gmp_add($total_in, $v);
    }

    $query = "
        SELECT SUM(amount) AS sum
        FROM requests
        WHERE curr_type='$type' AND req_type='WITHDR' AND status='VERIFY'
        ";
    $result = do_query($query);
    $row = get_row($result);
    if (isset($row['sum'])) {
        $v = gmp_init($row['sum']);
        $total_in = gmp_add($total_in, $v);
    }
    $total_in = gmp_strval($total_in);

    $total_out = gmp_init('0');
    $query = "
        SELECT SUM(amount) AS sum
        FROM requests
        WHERE curr_type='$type' AND req_type='DEPOS' AND status='FINAL'
        ";
    $result = do_query($query);
    $row = get_row($result);
    if (isset($row['sum'])) {
        $v = gmp_init($row['sum']);
        $total_out = gmp_add($total_out, $v);
    }

    $query = "
        SELECT SUM(amount) AS sum
        FROM requests
        WHERE curr_type='$type' AND req_type='WITHDR' AND status='FINAL'
        ";
    $result = do_query($query);
    $row = get_row($result);
    if (isset($row['sum'])) {
        $v = gmp_init($row['sum']);
        $total_out = gmp_sub($total_out, $v);
    }
    $total_out = gmp_strval($total_out);

    echo "$type = $total_in\t  $total_out\n";
    if (gmp_cmp($total_in, $total_out) != 0)
        echo "*********** MISMATCH ****************\n";
}

summa('BTC');    
summa('GBP');    
?>

