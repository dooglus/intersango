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
    return $v;
}

echo "BTC = " . summa('BTC') . "\n";    
echo "GBP = " . summa('GBP') . "\n";    
?>

