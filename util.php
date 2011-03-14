<?php
require 'db.php';
require 'jsonRPCClient.php';

class BASE_CURRENCY
{
    const A = 0;
    const B = 1;
}

function calc_exchange_rate($curr_a, $curr_b, $base_curr=BASE_CURRENCY::A)
{
    # how is the rate calculated? is it a/b or b/a?
    if ($base_curr == BASE_CURRENCY::A)
        $rate_calc_str = 'total_wanted/total_amount';
    else
        $rate_calc_str = 'total_amount/total_wanted';
    $query = "
        SELECT total_amount, total_wanted, $rate_calc_str AS rate
        FROM (
            SELECT SUM(amount) AS total_amount, SUM(want_amount) as total_wanted
            FROM orderbook
            WHERE type='$curr_a' AND want_type='$curr_b' AND status='OPEN'
            ) AS tbl;
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

function post($key)
{
    if (!isset($_POST[$key]))
        throw new Error('Ooops!', "Missing posted value $key!");
    $val = $_POST[$key];
    $val = preg_replace('[^A-Za-z0-9 ]', '', $val);
    return $val;
}

function connect_bitcoin()
{
    $bitcoin = new jsonRPCClient('http://user:password@127.0.0.1:8332/');
    return $bitcoin;
}
function bitcoin_balance()
{
    $uid = user_id();
    $bitcoin = connect_bitcoin();
    return $bitcoin->getbalance((string)$uid);
}
function bitcoin_deduct_funds($amount)
{
    $uid = user_id();
    $bitcoin = connect_bitcoin();
    $bitcoin->move((string)$uid, '', $amount);
}

function fetch_balances()
{
    $balances = array();
    $uid = user_id();
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
    # don't forget the ugly duckling
    $balances['BTC'] = bitcoin_balance();
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
    if ($curr_type == 'BTC') {
        if (gmp_cmp($amount, bitcoin_balance()) != 1)
            return true;
        else
            return false;
    }
    else {
        $query = "
            SELECT 1
            FROM purses
            WHERE uid='$uid' AND type='$curr_type' AND amount > '$amount'
            LIMIT 1;
        ";
        $result = do_query($query);
        return has_results($result);
    }
}

function deduct_funds($amount, $curr_type)
{
    $uid = user_id();
    if ($curr_type == 'BTC')
        bitcoin_deduct_funds($amount);
    else {
        $query = "
            UPDATE purses
            SET amount = amount - '$amount'
            WHERE uid='$uid' AND type='$curr_type';
        ";
        do_query($query);
    }
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
    $min_str = '0.05';
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
        case 'PROG':
            return 'In progress';
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
        default:
            throw new Error('No such request', 'This request is wrong...');
    }
}

?>

