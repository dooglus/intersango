<?php
$uid = $_SESSION['uid'];
if (!isset($uid)) {
    # grave error. This should never happen and should be reported as an urgent breach.
    throw new Error('Login 404', "You're not logged in. Proceed to the <a href='?login.php'>login</a> form.");
}

require 'db.php';
if (!isset($_POST['amount']) || !isset($_POST['type']) || !isset($_POST['want_amount']) || !isset($_POST['want_type']))
    throw new Error('Ooops!', 'No posted variables!');
$amount = $_POST['amount'];
$type = $_POST['type'];
$want_amount = $_POST['want_amount'];
$want_type = $_POST['want_type'];

# convert for inclusion into database
$amount = numstr_to_internal($amount);
$type = escapestr($type);
$want_amount = numstr_to_internal($want_amount);
$want_type = escapestr($want_type);

# make it grok'able
$amount = gmp_strval($amount);
$want_amount = gmp_strval($want_amount);

# find how whether user owns enough
$query = "SELECT amount FROM purses WHERE uid='".$uid."' AND type='".$type."' AND amount > '".$amount."';";
$result = do_query($query);
if (!has_results($result))   
    throw new Problem("Where's the gold?", "You don't have enough $type.");

# deduct money from their account
$query = "UPDATE purses SET amount = amount -'".$amount."' WHERE uid='".$uid."' AND type='".$type."';";
do_query($query);

# add the money to the order book
$query = "INSERT INTO orderbook(uid, amount, type, want_amount, want_type) VALUES ('".$uid."', '".$amount."', '".$type."', '".$want_amount."', '".$want_type."');";
$result = do_query($query);
?>
<div class='content_box'><h3>Order placed</h3>
<p>
<?php echo "Your order offering {$_POST['amount']} {$_POST['type']} for {$_POST['want_amount']} {$_POST['want_type']} has been placed."; ?>
</p>
</div>

