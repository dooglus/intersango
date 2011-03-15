<?php
require 'util.php';
require 'fulfill_order.php';
if (!isset($_GET['orderid']))
    throw new Problem('No order selected', 'Hit back and select an order.');
$orderid = $_GET['orderid'];
$uid = user_id();
$info = fetch_order_info($orderid);

if (isset($_POST['cancel_order'])) {
    # cancel an order
    $query = "
        UPDATE orderbook
        SET status='CANCEL'
        WHERE
            orderid='$orderid'
            AND uid='$uid';
    ";
    do_query($query);

    if ($uid != $info->uid)
        throw new Error('Permission...', '... Denied! Now GTFO.');

    add_funds($info->uid, $info->amount, $info->type);
    # these records indicate returned funds.
    create_record($orderid, $info->amount, -1, 0);
    ?><div class='content_box'>
        <h3>Cancelled!</h3>
        <p>Order <?php echo $orderid; ?> is no more.</p>
    </div><?php
}
else {
    $initial_amount = internal_to_numstr($info->initial_amount);
    $amount = internal_to_numstr($info->amount);
    $type = $info->type;
    $initial_want_amount = internal_to_numstr($info->initial_want_amount);
    $want_amount = internal_to_numstr($info->want_amount);
    $want_type = $info->want_type;
    $timest = $info->timest;
    $status = $info->status;
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

