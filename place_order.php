<?php
$uid = $_SESSION['uid'];
if (!isset($uid)) {
    # grave error. This should never happen and should be reported as an urgent breach.
    header('Location: index.php?page=login');
}
require 'db.php';
$amount = $_POST['amount'];
$type = $_POST['type'];
$want_amount = $_POST['want_amount'];
$want_type = $_POST['want_type'];
if (!isset($amount) || !isset($type) || !isset($want_amount) || !isset($want_type))
    die('No posted variables!');
# convert for inclusion into database
$amount = numstr_to_internal($amount);
$type = escapestr($type);
$want_amount = numstr_to_internal($want_amount);
$want_type = escapestr($want_type);

# find how much user actually owns
$query = "SELECT amount FROM purses WHERE uid='".$uid."' AND type='".$type."';";
$result = do_query($query);
$row = mysql_fetch_array($result);
if (!$row)
    die('You don\'t own that type of currency!');
$owned_amount = $row['amount'];

if ($amount > $owned_amount)
    die('You do not have enough currency.');
# deduct money from their account
$owned_amount -= $amount;
$query = "UPDATE purses SET amount='".$owned_amount."' WHERE uid='".$uid."' AND type='".$type."';";
do_query($query);

# add the money to the order book
$query = "INSERT INTO orderbook(uid, amount, type, want_amount, want_type) VALUES ('".$uid."', '".$amount."', '".$type."', '".$want_amount."', '".$want_type."');";
$result = do_query($query);
?>
