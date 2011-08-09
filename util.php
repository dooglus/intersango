<?php
require_once 'db.php';
require_once 'jsonRPCClient.php';

class BASE_CURRENCY
{
    const A = 0;
    const B = 1;
}

function create_record($our_orderid,  $our_amount,  $our_commission,
                       $them_orderid, $them_amount, $them_commission)
{
    // record keeping
    $query = "
        INSERT INTO transactions (
            a_orderid,
            a_amount,
            a_commission,
            b_orderid,
            b_amount,
            b_commission
        ) VALUES (
            '$our_orderid',
            '$our_amount',
            '$our_commission',
            '$them_orderid',
            '$them_amount',
            '$them_commission'
        );
    ";
    do_query($query);
}

function add_funds($uid, $amount, $type)
{
    # eventually plan to move these to prepared mysql statements once the queries become more mature.
    $query = "
        UPDATE purses
        SET
            amount = amount + '$amount'
        WHERE
            uid='$uid'
            AND type='$type';
        ";
    do_query($query);
}

function calc_exchange_rate($curr_a, $curr_b, $base_curr=BASE_CURRENCY::A)
{
    # how is the rate calculated? is it a/b or b/a?
    if ($base_curr == BASE_CURRENCY::A)
        $invertor = 'TRUE';
    else
        $invertor = 'FALSE';
    $query = "
        SELECT
            SUM(amount) AS total_amount,
            SUM(want_amount) as total_wanted,
            ROUND(
                IF(
                    $invertor,
                    MIN(initial_want_amount/initial_amount),
                    MAX(initial_amount/initial_want_amount)
                ), 4) AS rate
        FROM
            orderbook
        WHERE
            type='$curr_a'
            AND want_type='$curr_b'
            AND status='OPEN'
        ";
    $total_result = do_query($query);
    list($total_amount, $total_want_amount, $rate) = mysql_fetch_array($total_result);
    if (!isset($total_amount) || !isset($total_want_amount) || !isset($rate)) 
        return NULL;
    $total_amount = internal_to_numstr($total_amount);
    $total_want_amount = internal_to_numstr($total_want_amount);
    $rate = clean_sql_numstr($rate);
    return array($total_amount, $total_want_amount, $rate);
}

function logout()
{
    session_destroy();

    // expire the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 36*60*60, $params["path"],   $params["domain"], $params["secure"], $params["httponly"]);
    }
    header('Location: .');
    exit();
}

function is_logged_in()
{
    if (!isset($_SESSION['uid']) || !isset($_SESSION['oidlogin']))
        return 0;

    // just having a 'uid' in the session isn't enough to be logged in
    // check that the oidlogin matches the uid in case database has been reset
    $uid = $_SESSION['uid'];
    $oidlogin = $_SESSION['oidlogin'];

    if (has_results(do_query("
        SELECT uid
        FROM users
        WHERE oidlogin = '$oidlogin'
        AND uid = '$uid'
    ")))
        return $uid;

    logout();
}

function is_admin()
{
    if (!isset($_SESSION['uid']) || !isset($_SESSION['oidlogin']))
        return false;

    // just having a 'uid' in the session isn't enough to be logged in
    // check that the oidlogin matches the uid in case database has been reset
    $uid = $_SESSION['uid'];
    $oidlogin = $_SESSION['oidlogin'];

    return has_results(do_query("
        SELECT uid
        FROM users
        WHERE oidlogin = '$oidlogin'
        AND uid = '$uid'
        AND is_admin = 1
    "));
}
    
function user_id()
{
    if (!isset($_SESSION['uid'])) {
        # grave error. This should never happen and should be reported as an urgent breach.
        throw new Error('Login 404', "You're not logged in. Proceed to the <a href='?login.php'>login</a> form.");
    }
    return $_SESSION['uid'];
}

function get_lock()
{
    $uid = user_id();
    $lock = lock_dir() . $uid;

    if (!($fp = fopen($lock, "w")))
        throw new Error('Lock Error', "Can't create lockfile for $uid");

    if (!flock($fp, LOCK_EX|LOCK_NB))
        throw new Error('Lock Error', "User $uid is already doing stuff.<br/>");

    return $fp;
}

function release_lock($fp)
{
    flock($fp, LOCK_UN);
}

function cleanup_string($val)
{
    $val = preg_replace('/[^A-Za-z0-9 .]/', '', $val);
    return mysql_real_escape_string($val);
}
function post($key)
{
    if (!isset($_POST[$key]))
        throw new Error('Ooops!', "Missing posted value $key!");
    return cleanup_string($_POST[$key]);
}
function get($key)
{
    if (!isset($_GET[$key]))
        throw new Error('Ooops!', "Missing get value $key!");
    return cleanup_string($_GET[$key]);
}

function sync_to_bitcoin($uid)
{
    $bitcoin = connect_bitcoin();
    try {
        $balance = $bitcoin->getbalance($uid, confirmations_for_deposit());

        if (gmp_cmp($balance, '0') > 0) {
            $bitcoin->move($uid, '', $balance);
            $query = "
            INSERT INTO requests (req_type, uid, amount, curr_type)
            VALUES ('DEPOS', '$uid', '$balance', 'BTC');
        ";
            do_query($query);
        }
    } catch (Exception $e) {
        if ($e->getMessage() != 'Unable to connect.')
            throw $e;
    }
}

function fetch_committed_balances($uid)
{
    // returns an array of amounts of balances currently committed in unfilled orders
    $balances = array('AUD'=>'0','BTC'=>'0');
    sync_to_bitcoin($uid);
    $query = "
        SELECT sum(amount) as amount, type
        FROM orderbook
        WHERE uid = '$uid'
              AND status = 'OPEN'
        GROUP BY type;
    ";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $amount = $row['amount'];
        $type = $row['type'];
        $balances[$type] = $amount;
    }
    return $balances;
}

function fetch_balances($uid)
{
    $balances = array();
    sync_to_bitcoin($uid);
    $query = "
        SELECT amount, type
        FROM purses
        WHERE uid='$uid';
    ";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $amount = $row['amount'];
        $type = $row['type'];
        $balances[$type] = $amount;
    }
    return $balances;
}

function show_committed_balances($uid, $indent=false)
{
    $balances = fetch_committed_balances($uid);
    if ($indent)
        echo "<p class='indent'>";
    else
        echo "<p>";
    echo "You have ", internal_to_numstr($balances['AUD']), " AUD and ",
        internal_to_numstr($balances['BTC']), " BTC ",
        "tied up in the orderbook.</p>\n";
}

function show_balances($uid, $indent=false)
{
    $balances = fetch_balances($uid);
    foreach($balances as $type => $amount) {
        $amount = internal_to_numstr($amount);
        if ($indent)
            echo "<p class='indent'>";
        else
            echo "<p>";
        echo "You have $amount $type.</p>\n";
    }
}

function get_ticker_data()
{
    $query = "
    SELECT
        SUM(b_amount + b_commission) AS vol
    FROM
        transactions
    WHERE
        b_amount > 0
        AND timest BETWEEN NOW() - INTERVAL 1 DAY AND NOW()
    ";
    $result = do_query($query);
    $row = get_row($result);
    if (isset($row['vol']))
        $vol = internal_to_numstr($row['vol'], 4);
    else
        $vol = 0;

    $exchange_fields = calc_exchange_rate('AUD', 'BTC', BASE_CURRENCY::B);
    if (!$exchange_fields)
        $buy = 0;
    else
        list($total_amount, $total_want_amount, $buy) = $exchange_fields; 

    $exchange_fields = calc_exchange_rate('BTC', 'AUD', BASE_CURRENCY::A);
    if (!$exchange_fields)
        $sell = 0;
    else
        list($total_amount, $total_want_amount, $sell) = $exchange_fields; 

    $query = "
    SELECT
        a_amount,
        ord_a.type AS a_type,
        b_amount,
        ord_b.type AS b_type
    FROM
        transactions AS t
    JOIN
        orderbook AS ord_a
    ON
        ord_a.orderid=a_orderid
    JOIN
        orderbook AS ord_b
    ON
        ord_b.orderid=b_orderid
    WHERE
        b_amount >= 0
    ORDER BY
        t.timest DESC
    LIMIT 1
    ";
    $result = do_query($query);
    if (has_results($result)) {
        $row = get_row($result);
        $a_amount = $row['a_amount'];
        $a_type = $row['a_type'];
        $b_amount = $row['b_amount'];
        $b_type = $row['b_type'];
        if ($a_type == 'AUD' && $b_type == 'BTC')
            $last = bcdiv($a_amount, $b_amount, 4);
        else
            echo "this never happens<br/>\n";
    }
    else
        $last = 0;

    return array($vol, $buy, $sell, $last);
}

function has_enough($amount, $curr_type)
{
    $uid = user_id();
    sync_to_bitcoin($uid);
    $query = "
        SELECT 1
        FROM purses
        WHERE uid='$uid' AND type='$curr_type' AND amount >= '$amount'
        LIMIT 1;
    ";
    $result = do_query($query);
    return has_results($result);
}

function active_table_row($class, $url)
{
    printf ("<tr %s %s %s %s>",
            "class=\"$class\"",
            'onmouseover="style.backgroundColor=\'#8ae3bf\';"',
            'onmouseout="style.backgroundColor=\'#7ad3af\';"',
            "onclick=\"document.location='$url';\"");
}

class OrderInfo
{
    public $orderid, $uid, $initial_amount, $amount, $type, $initial_want_amount, $want_amount, $want_type, $commission, $status, $timest, $processed;

    public function __construct($row)
    {
        $this->orderid = $row['orderid'];
        $this->uid = $row['uid'];
        $this->initial_amount = $row['initial_amount'];
        $this->amount = $row['amount'];
        $this->type = $row['type'];
        $this->initial_want_amount = $row['initial_want_amount'];
        $this->want_amount = $row['want_amount'];
        $this->want_type = $row['want_type'];
        $this->commission = $row['commission'];
        $this->status = $row['status'];
        $this->timest = $row['timest_format'];
        $this->processed = (bool)$row['processed'];
    }
}

function fetch_order_info($orderid)
{
    $query = "
        SELECT
            *,
            DATE_FORMAT(timest, '%H:%i %d/%m/%y') AS timest_format
        FROM orderbook
        WHERE orderid='$orderid';
    ";
    $result = do_query($query);
    $row = get_row($result);
    $info = new OrderInfo($row);
    return $info;
}

function deduct_funds($amount, $curr_type)
{
    add_funds(user_id(), -$amount, $curr_type);
}

function curr_supported_check($curr_type)
{
    $supported_currencies = array('AUD', 'BTC');
    if (!in_array($curr_type, $supported_currencies))
        throw new Error('Ooops!', 'Bad currency supplied.');
}
function order_worthwhile_check($amount, $amount_disp, $min_str='0.5')
{
    if (!is_numeric($amount_disp))
        throw new Problem('Numbers. Numbers.', 'The value you entered was not a number.');
    $min = numstr_to_internal($min_str);
    if ($amount < $min)
        throw new Problem("Try again...", "Your order size is too small. The minimum is $min_str.");
}
function enough_money_check($amount, $curr_type)
{
    if (!has_enough($amount, $curr_type))
        throw new Problem("Where's the gold?", "You don't have enough $curr_type.");
}

function translate_order_code($code)
{
    # OPEN CANCEL CLOSED
    switch ($code)
    {
        case 'OPEN':
            return 'Open';
        case 'CANCEL':
            return 'Cancelled';
        case 'CLOSED':
            return 'Completed';
        default:
            throw new Error('No such order', 'This order is wrong...');
    }
}

function translate_request_type($type)
{
    switch ($type)
    {
        case 'WITHDR':
            return 'Withdraw';
        case 'DEPOS':
            return 'Deposit';
        default:
            throw new Error('No such request type', 'This request is wrong...');
    }
}
function translate_request_code($code)
{
    # VAL VERIF PRO OK FIN NO RET
    # jei verifies payments
    # I verify (process) payments
    # we both confirm (OK) them
    # they either complete (SENT, FIN) or deny (NO, RET)
    switch ($code)
    {
        case 'VERIFY':
            return 'Verifying';
        case 'PROCES':
            return 'Processing';
        case 'FINAL':
            return 'Finished';
        case 'IGNORE':
            return 'Ignored';
        case 'REJECT':
            return 'Rejected';
        case 'CANCEL':
            return 'Cancelled';
        default:
            throw new Error('No such request', 'This request is wrong...');
    }
}

function get_time_text()
{
    // see http://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format
    $result = do_query("SELECT CONCAT(DATE_FORMAT(now, '%l:%i')," .
                       "LOWER(DATE_FORMAT(now, '%p'))," .
                       "DATE_FORMAT(now, ' on %W')) AS time" .
                       " FROM (SELECT NOW() AS now) now");
    $row = mysql_fetch_assoc($result);
    return $row['time'];
}

function show_commission_rates()
{
    echo "<blockquote>\n";

    $cap = commission_cap_in_btc();
    $rate = commission_percentage_for_btc();
    if ($rate == 0)
        echo "<p>buying BTC is free of commission</p>\n";
    else {
        echo "<p>$rate%";
        if ($cap)
            echo " (capped at $cap BTC)";
        else
            echo " (uncapped)";
        echo " when buying BTC</p>\n";
    }

    $cap = commission_cap_in_aud();
    $rate = commission_percentage_for_aud();
    if ($rate == 0)
        echo "<p>buying AUD is free of commission</p>\n";
    else {
        echo "<p>$rate%";
        if ($cap)
            echo " (capped at $cap AUD)";
        else
            echo " (uncapped)";
        echo " when selling BTC</p>\n";
    }

    echo "</blockquote>\n";
}

function take_commission($amount, $curr_type, $orderid)
{
    add_funds(1, $amount, $curr_type);

    $result = do_query("
        SELECT COUNT(*) AS count
        FROM orderbook
        WHERE orderid='$orderid'
        AND want_type = '$curr_type'
    ");

    $row = mysql_fetch_assoc($result);
    if ($row['count'] != 1)
        throw new Error('Error taking commission', "Mismatched currency types");

    $result = do_query("
        UPDATE orderbook
        SET commission = commission + $amount
        WHERE orderid='$orderid'
        AND want_type = '$curr_type'
    ");
}

function commission($amount, $percentage, $cap, $already_paid)
{
    $commission = gmp_div(gmp_mul((string)$amount,
                                  numstr_to_internal((string)$percentage)),
                          numstr_to_internal(100));

    // reduce the cap by the amount we already paid, but no lower than 0
    if (!$cap) return gmp_strval($commission);

    $cap = max(gmp_strval(gmp_sub(numstr_to_internal($cap), $already_paid)), '0');
    return min(gmp_strval($commission), $cap);
}

function commission_on_aud($aud, $already_paid)
{
    return commission($aud,
                      commission_percentage_for_aud(),
                      commission_cap_in_aud(),
                      $already_paid);
}

function commission_on_btc($btc, $already_paid)
{
    return commission($btc,
                      commission_percentage_for_btc(),
                      commission_cap_in_btc(),
                      $already_paid);
}

// calculate and return the commission to pay on $amount of type $curr_type if $already_paid has already been paid on this order
function commission_on_type($amount, $curr_type, $already_paid)
{
    if ($curr_type == 'AUD')
        return commission_on_aud($amount, $already_paid);

    if ($curr_type == 'BTC')
        return commission_on_btc($amount, $already_paid);

    throw new Error('Unknown currency type', "Type $curr_type isn't AUD or BTC");
}

function btc_withdrawn_today($uid)
{
    $query = "
        SELECT SUM(amount) as sum
        FROM requests
        WHERE timest > CURRENT_DATE()
        AND uid = $uid
        AND req_type = 'WITHDR'
        AND curr_type = 'BTC'
    ";
    $result = do_query($query);
    $row = get_row($result);
    $sum = $row['sum'];
    if (!$sum) $sum = '0';
    return $sum;
}

function check_btc_withdraw_limit($uid, $amount)
{
    $withdrawn = btc_withdrawn_today($uid);
    $limit = numstr_to_internal(maximum_daily_btc_withdraw());
    $available = gmp_sub($limit, $withdrawn);

    if (gmp_cmp($amount, $available) > 0)
        throw new Problem('Daily limit exceeded', 'You can only withdraw '.internal_to_numstr($limit).' BTC per day.');
}

function check_withdraw_limit($uid, $amount, $curr_type)
{
    if ($curr_type == 'BTC')
        check_btc_withdraw_limit($uid, $amount);
    else
        check_aud_transfer_limit($uid, $amount);
}

?>
