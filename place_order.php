<?php
require_once 'order_utils.php';

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

$have_amount   = post('amount');
$have_currency = post('type');
$want_amount   = post('want_amount');
$want_currency = post('want_type');

$_SESSION['currency_in'] = strtoupper($have_currency);

$orderid = place_order($have_amount, $have_currency, $want_amount, $want_currency);

?>

<div class='content_box'>
    <h3><?php echo _("Order placed"); ?></h3>

    <p><?php printf(_("Your order offering %s for %s has been placed."),
                    $have_amount . " " . $have_currency,
                    $want_amount . " " . $want_currency); ?></p>

    <p><?php printf(_("You may view your %snew order%s or visit the %sorderbook%s."),
                    '<a href="?page=view_order&orderid=' . $orderid . '">',
                    '</a>',
                    '<a href="?page=orderbook">',
                    '</a>'); ?></p>
</div>
