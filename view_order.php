<?php
require 'util.php';
if (!isset($_GET['orderid']))
    throw new Problem('No order selected', 'Hit back and select an order.');
$orderid = $_GET['orderid'];

if (isset($_POST['cancel_order'])) {
    # cancel an order
    $query = "
        UPDATE orderbook
        SET status='CANCEL'
        WHERE orderid='$orderid';
    ";
    do_query($query);
    ?><div class='content_box'>
        <h3>Cancelled!</h3>
        <p>Order <?php echo $orderid; ?> is no more.</p>
    </div><?php
}
else {
    $query = "
        SELECT
            initial_amount,
            amount,
            type,
            initial_want_amount,
            want_amount,
            want_type,
            DATE_FORMAT(timest, '%H%i %d/%m/%y') AS timest,
            status
        FROM orderbook
        WHERE orderid='$orderid';
    ";
    $result = do_query($query);
    $row = get_row($result);
    $initial_amount = internal_to_numstr($row['initial_amount']);
    $amount = internal_to_numstr($row['amount']);
    $type = $row['type'];
    $initial_want_amount = internal_to_numstr($row['initial_want_amount']);
    $want_amount = internal_to_numstr($row['want_amount']);
    $want_type = $row['want_type'];
    $timest = $row['timest'];
    $status = $row['status'];
    ?> <div class='content_box'>
        <h3>Order info</h3>
        <p>
        Order <?php echo $orderid; ?>
        </p>
        <p>
        When the order was placed: <?php echo "$initial_amount $type"; ?> for <?php echo "$initial_want_amount $want_type"; ?>
        </p>
        <p>
        <?php echo "$amount $type"; ?> for <?php echo "$want_amount $want_type"; ?> remaining.
        </p>
        <p>
        Made <?php echo $timest; ?>
        </p>
        <p>
        <?php if ($status == 'OPEN') { ?>
            <form action='' class='indent_form' method='post'>
                <input type='hidden' name='cancel_order' value='true' />
                <input type='submit' value='Cancel order' />
            </form>
        <?php } ?>
        </p>
    </div> <?php
}
?>

