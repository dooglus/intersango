<?php
require_once 'openid.php';
require_once 'util.php';
require_once 'view_util.php';

if (!isset($_SESSION['uid']))
    throw new Error('Denied', 'Go away.');

echo "   <div class='content_box'>\n";
echo "<h3>Private user info</h3>\n";
# main info
echo "<p>You are logged in.</p>\n";
$uid = $_SESSION['uid'];
$oidlogin = $_SESSION['oidlogin'];
echo "<p>User ID: $uid</p>\n";
echo "<p>OpenID: $oidlogin</p>\n";
show_balances($uid);
show_committed_balances($uid);
check_aud_balance_limit($uid, "0");
echo "</div>\n";

$query = "
    SELECT
        orderid,
        amount,
        initial_amount,
        type,
        initial_want_amount,
        want_type,
        " . sql_format_date("timest") . " AS timest,
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
            <th>Price</th>
            <th>Time</th>
            <th>Status<br/>(% matched)</th>
            <th>Trades</th>
        </tr><?php
    do {
        $orderid = $row['orderid'];
        $amount = internal_to_numstr($row['amount']);
        $initial_amount = internal_to_numstr($row['initial_amount']);
        $type = $row['type'];
        $initial_want_amount = internal_to_numstr($row['initial_want_amount']);
        $want_type = $row['want_type'];
        $timest = $row['timest'];
        $timest = str_replace(" ", "<br/>", $timest);
        $status_code = $row['status'];
        $status = translate_order_code($status_code);
        $price = sprintf("%.6f", ($type == 'BTC') ? $initial_want_amount / $initial_amount : $initial_amount / $initial_want_amount);
        $percent_complete = sprintf("%.0f", ($initial_amount - $amount) * 100.0 / $initial_amount);
        $trade_count = count_transactions($orderid);
        echo "    ", active_table_row("active", "?page=view_order&orderid=$orderid"), "\n";
        echo "        <td>$initial_amount&nbsp;$type</td>\n";
        echo "        <td>$initial_want_amount&nbsp;$want_type</td>\n";
        echo "        <td>$price</td>\n";
        echo "        <td>$timest</td>\n";
        echo "        <td>$status<br/>($percent_complete%)</td>\n";
        echo "        <td>$trade_count</td>\n";
        echo "    </tr>\n";
    } while ($row = mysql_fetch_assoc($result));
    echo "</table></div>";
}

# also used when you view an order
display_transactions($uid, 0);

$query = "
    SELECT
        reqid,
        req_type,
        amount,
        curr_type,
        " . sql_format_date("timest") . " AS timest,
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

try {
    $bitcoin = connect_bitcoin();
    $needed_conf = confirmations_for_deposit();
    $balance = $bitcoin->getbalance($uid, $needed_conf);

    if ($balance != $bitcoin->getbalance($uid, 0)) { ?>
    <div class='content_box'>
    <h3>Pending bitcoin deposits</h3>
    <table class='display_data'>
        <tr>
            <th>Amount</th>
            <th>Confirmations Received</th>
            <th>More Confirmations Needed</th>
        </tr>
    <?php
        for ($conf = $needed_conf; $conf >= 0; $conf--) {
            $new_balance = $bitcoin->getbalance($uid, $conf);
            if ($balance != $new_balance) {
                $diff = gmp_sub($new_balance, $balance);
                echo "<tr><td>", internal_to_numstr($diff), "</td><td>$conf</td><td>", $needed_conf - $conf, "</td></tr>\n";
                $balance = $new_balance;
            }
        }
        echo "</table></div>";
    }
} catch (Exception $e) {
    if ($e->getMessage() != 'Unable to connect.')
        throw $e;
    echo "<div class='content_box'>\n";
    echo "<h3>Pending bitcoin deposits</h3>\n";
    echo "<p>Normally this area would display any bitcoin deposits you have made that are awaiting confirmations, but we are having trouble connecting to the bitcoin network at the moment, so it doesn't.</p>\n";
    echo "<p>Please try again in a few minutes.</p>\n";
    echo "</div>";
}
?>
