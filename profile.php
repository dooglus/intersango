<?php
require_once 'openid.php';
require_once 'util.php';
require_once 'view_util.php';

if (!isset($_SESSION['uid']))
    throw new Error('Denied', 'Go away.');

echo "   <div class='content_box'>";
echo '<h3>Private user info</h3>';
# main info
echo '<p>You are logged in.</p>';
$uid = $_SESSION['uid'];
$oidlogin = $_SESSION['oidlogin'];
echo '<p>User ID: '.$uid.'</p>';
echo '<p>OpenID: '.$oidlogin.'</p>';
show_balances();
echo '<p>Balances above do not include funds in the orderbook.</p>';
echo '</div>';

$query = "
    SELECT
        orderid,
        IF(status='OPEN', amount, initial_amount) AS amount,
        type,
        IF(status='OPEN', want_amount, initial_want_amount) AS want_amount,
        want_type,
        DATE_FORMAT(timest, '%H:%i %d/%m/%y') AS timest,
        status
    FROM orderbook
    WHERE uid='$uid'
    ORDER BY orderbook.timest DESC;
";
$result = do_query($query);
$row = mysql_fetch_assoc($result);
if ($row) { ?>
    <div class='content_box'>
    <h3>Your orders</h3>
    <table class='display_data'>
        <tr>
            <th>Giving</th>
            <th>Wanted</th>
            <th>Time</th>
            <th>Status</th>
            <th></th>
        </tr><?php
    do {
        $orderid = $row['orderid'];
        $amount = internal_to_numstr($row['amount']);
        $type = $row['type'];
        $want_amount = internal_to_numstr($row['want_amount']);
        $want_type = $row['want_type'];
        $timest = $row['timest'];
        $status = $row['status'];
        $status = translate_order_code($status);
        echo "    <tr>\n";
        echo "        <td>$amount $type</td>\n";
        echo "        <td>$want_amount $want_type</td>\n";
        echo "        <td>$timest</td>\n";
        echo "        <td>$status</td>\n";
        echo "        <td><a href='?page=view_order&orderid=$orderid'>View order</a></td>\n";
        echo "    </tr>\n";
    } while ($row = mysql_fetch_assoc($result));
    echo "</table></div>";
}

# also used when you view an order
display_transactions($uid, -1);

$query = "
    SELECT
        reqid,
        req_type,
        amount,
        curr_type,
        DATE_FORMAT(timest, '%H:%i %d/%m/%y') AS timest,
        status
    FROM requests
    WHERE
        uid='$uid' 
        AND (req_type='WITHDR' OR req_type='DEPOS') 
        AND status!='IGNORE'
    ORDER BY requests.timest DESC;
";
$result = do_query($query);
$row = mysql_fetch_assoc($result);
if ($row) { ?>
    <div class='content_box'>
    <h3>Your requests</h3>
    <table class='display_data'>
        <tr>
            <th>Amount</th>
            <th>Time</th>
            <th>Status</th>
            <th></th>
        </tr><?php
    do {
        $reqid = $row['reqid'];
        $req_type = $row['req_type'];
        $req_type = translate_request_type($req_type);
        $amount = internal_to_numstr($row['amount']);
        $curr_type = $row['curr_type'];
        $timest = $row['timest'];
        $status = $row['status'];
        $status = translate_request_code($status);
        echo "    <tr>\n";
        echo "        <td>$req_type $amount $curr_type</td>\n";
        echo "        <td>$timest</td>\n";
        echo "        <td>$status</td>\n";
        echo "        <td><a href='?page=view_request&reqid=$reqid'>View request</a></td>\n";
        echo "    </tr>\n";
    } while ($row = mysql_fetch_assoc($result));
    echo "</table></div>";
}
?>

