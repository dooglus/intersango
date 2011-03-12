<?php
$uid = $_SESSION['uid'];
if (!isset($uid)) {
    # grave error. This should never happen and should be reported as an urgent breach.
    throw new Error('Login 404', "You're not logged in. Proceed to the <a href='?login.php'>login</a> form.");
}

if (!isset($_POST['amount']) || !isset($_POST['type']) || !isset($_POST['want_amount']) || !isset($_POST['want_type']))
    throw new Error('Ooops!', 'No posted variables!');

$type = $_POST['type'];
$type = strtoupper($type);
$want_type = $_POST['want_type'];
$want_type = strtoupper($want_type);

$supported_currencies = array('GBP', 'BTC');
if (!in_array($type, $supported_currencies) || !in_array($want_type, $supported_currencies))
    throw new Error('Ooops!', 'Bad currency supplied.');

require 'util.php';

# convert for inclusion into database
$amount = $_POST['amount'];
$amount = numstr_to_internal($amount);
$want_amount = $_POST['want_amount'];
$want_amount = numstr_to_internal($want_amount);

# make it grok'able
$amount = gmp_strval($amount);
$want_amount = gmp_strval($want_amount);

# find how whether user owns enough
if (!has_enough($amount, $type))
    throw new Problem("Where's the gold?", "You don't have enough $type.");

# deduct money from their account
deduct_funds($amount, $type);

# add the money to the order book
$query = "INSERT INTO orderbook(uid, amount, type, want_amount, want_type) VALUES ('".$uid."', '".$amount."', '".$type."', '".$want_amount."', '".$want_type."');";
$result = do_query($query);
?>
<div class='content_box'><div class='content_sideshadow'>
<h3>Order placed</h3>
<p>
<?php echo "Your order offering {$_POST['amount']} $type for {$_POST['want_amount']} $want_type has been placed."; ?>
</p>
<p>You may visit the <a href='?page=orderbook'>orderbook</a>.
</div>
</div>

