<?php
require_once 'order_utils.php';
require_once 'view_util.php';

if(isset($_POST['cancel_order']))
{
    if(isset($_POST['csrf_token']))
    {
        if($_SESSION['csrf_token'] != $_POST['csrf_token'])
        {
            throw new Error("csrf","csrf token mismatch!");
        }
    }
    else
    {
        throw new Error("csrf","csrf token missing!");
    }
}

if (!isset($_GET['orderid']))
    throw new Problem(_('No order selected'), _('Hit back and select an order.'));
$orderid = get('orderid');
$uid = user_id();
$info = fetch_order_info($orderid);
if ($is_admin)
    $uid = $info->uid;
else if ($info->uid != $uid)
    throw new Problem('Not for your eyes', "This isn't your order.");

if (isset($_POST['cancel_order'])) {
    cancel_order($orderid, $uid);

    ?><div class='content_box'>
        <h3>Cancelled!</h3>
        <p>Order <?php echo $orderid; ?> is no more.</p>
        <p>Back to <a href="?page=orderbook">the orderbook</a>.</p>
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
        <?php printf(_("Order %s"), $orderid); ?>
        </p>
        <p>
        <?php printf(_("When the order was placed: %s for %s"),
                     "$initial_amount $type",
                     "$initial_want_amount $want_type"); ?>
        </p>
        <?php if ($status == 'OPEN') {
            echo "<p>$amount $type for $want_amount $want_type remaining.</p>";
        } ?>
        <p>
        <?php printf(_("Made %s"), $timest); if ($is_logged_in != $uid) echo " " . sprintf(_("by user %s"), $uid); ?>
        </p>
        <p>
        <?php echo translate_order_code($status); ?>
        </p>
        <?php if ($status == 'OPEN') { ?>
        <p>
            <form action='' class='indent_form' method='post'>
                <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
                <input type='hidden' name='cancel_order' value='true' />
                <input type='submit' value='Cancel order' />
            </form>
        </p>
        <?php } ?>
    </div> <?php
    display_transactions($uid, $orderid);
}
?>
