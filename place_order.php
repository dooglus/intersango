<?php
require_once 'util.php';
require_once "errors.php";

if(isset($_POST['csrf_token']))
{
    if($_SESSION['csrf_token'] != $_POST['csrf_token'])
    {
        throw new Error("csrf","csrf token mismatch!");
    }
}
else
{
    throw new Error("csrf","csrf token missing");
}

$uid = user_id();

$type = post('type');
$type = strtoupper($type);
$want_type = post('want_type');
$want_type = strtoupper($want_type);

$_SESSION['currency_in'] = $type;

curr_supported_check($type);
curr_supported_check($want_type);

// convert for inclusion into database
$amount_disp = post('amount');
$amount = numstr_to_internal($amount_disp);
$want_amount_disp = post('want_amount');
$want_amount = numstr_to_internal($want_amount_disp);

order_worthwhile_check($amount, $amount_disp, MINIMUM_HAVE_AMOUNT);
order_worthwhile_check($want_amount, $want_amount_disp, MINIMUM_WANT_AMOUNT);

enough_money_check($amount, $type);

do_query("START TRANSACTION");
// deduct money from their account
deduct_funds($amount, $type);

// add the money to the order book
$query = "
    INSERT INTO orderbook (
        uid,
        initial_amount,
        amount,
        type,
        initial_want_amount,
        want_amount,
        want_type)
    VALUES (
        '$uid',
        '$amount',
        '$amount',
        '$type',
        '$want_amount',
        '$want_amount',
        '$want_type');
    ";
$result = do_query($query);
$orderid = mysql_insert_id();
do_query("COMMIT");
?>

<div class='content_box'>
    <h3><?php echo _("Order placed"); ?></h3>
    <p><?php printf(_("Your order offering %s for %s has been placed."),
                    $amount_disp . " " . $type,
                    $want_amount_disp . " " . $want_type); ?></p>
    <p><?php printf(_("You may view your %snew order%s or visit the %sorderbook%s."),
                    '<a href="?page=view_order&orderid=' . $orderid . '">',
                    '</a>',
                    '<a href="?page=orderbook">',
                    '</a>'); ?></p>
</div>
