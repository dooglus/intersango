<?php
require 'db.php';
require 'jsonRPCClient.php';

class BASE_CURRENCY
{
    const A = 0;
    const B = 1;
}

function create_record($our_orderid, $our_amount, $them_orderid, $them_amount)
{
    # record keeping
    $query = "
        INSERT INTO transactions (
            a_orderid,
            a_amount,
            b_orderid,
            b_amount
        ) VALUES (
            '$our_orderid',
            '$our_amount',
            '$them_orderid',
            '$them_amount'
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

function user_id()
{
    if (!isset($_SESSION['uid'])) {
        # grave error. This should never happen and should be reported as an urgent breach.
        throw new Error('Login 404', "You're not logged in. Proceed to the <a href='?login.php'>login</a> form.");
    }
    return $_SESSION['uid'];
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
    $balance = $bitcoin->getbalance($uid, 4);
    #$query = "
    #    UPDATE purses
    #    SET amount = amount + '$balance'
    #    WHERE uid='$uid' AND type='BTC';
    #";
    #do_query($query);
    if (gmp_cmp($balance, '0') > 0) {
        $query = "
            INSERT INTO requests (req_type, uid, amount, curr_type)
            VALUES ('DEPOS', '$uid', '$balance', 'BTC');
        ";
        do_query($query);
        $bitcoin->move($uid, '', $balance);
    }
}

function fetch_balances()
{
    $balances = array();
    $uid = user_id();
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

function show_balances($indent=false)
{
    $balances = fetch_balances();
    foreach($balances as $type => $amount) {
        $amount = internal_to_numstr($amount);
        if ($indent)
            echo "<p class='indent'>";
        else
            echo "<p>";
        echo "You have $amount $type.</p>\n";
    }
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

class OrderInfo
{
    public $orderid, $uid, $initial_amount, $amount, $type, $initial_want_amount, $want_amount, $want_type, $status, $timest, $processed;

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
    $uid = user_id();
    $query = "
        UPDATE purses
        SET amount = amount - '$amount'
        WHERE uid='$uid' AND type='$curr_type';
    ";
    do_query($query);
}

function curr_supported_check($curr_type)
{
    $supported_currencies = array('GBP', 'BTC');
    if (!in_array($curr_type, $supported_currencies))
        throw new Error('Ooops!', 'Bad currency supplied.');
}
function order_worthwhile_check($amount, $amount_disp)
{
    if (!is_numeric($amount_disp))
        throw new Problem('Numbers. Numbers.', 'The value you entered was not a number.');
    $min_str = '0.5';
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
        case 'CANCEL':
            return 'Cancelled';
        default:
            throw new Error('No such request', 'This request is wrong...');
    }
}

?>

