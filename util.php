<?php
require_once 'db.php';
require_once 'jsonRPCClient.php';

class BASE_CURRENCY
{
    const A = 0;
    const B = 1;
}

function fiat_to_numstr($num)
{
    return sprintf("%." . FIAT_PRECISION . "f %s", $num, CURRENCY);
}

function btc_to_numstr($num)
{
    return sprintf("%." . BTC_PRECISION . "f %s", $num, "BTC");
}

function show_contact_info()
{
    echo "<h3>" . _("Contact info") . "</h3>\n";
    printf("<p>%s: <a href=\"mailto:%s\">%s</a></p>\n",
           _("Email"),
           CONTACT_EMAIL_ADDRESS, CONTACT_EMAIL_ADDRESS);
    printf("<p>%s: <a href=\"skype:%s?call\">%s</a></p>\n",
           _("Skype"),
           CONTACT_SKYPE_ADDRESS, CONTACT_SKYPE_ADDRESS);
    printf("<p>%s: <a target=\"_blank\" href=\"%s\">%s</a></p>\n",
           _("Facebook"),
           CONTACT_FACEBOOK_URL, CONTACT_FACEBOOK_NAME);
    printf("<p>%s: <a target=\"_blank\" href=\"http://twitter.com/%s\">@%s</a></p>\n",
           _("Twitter"),
           CONTACT_TWITTER_NAME, CONTACT_TWITTER_NAME);
    printf("<p>%s: %s</p>\n",
           _("Call"),
           CONTACT_PHONE_NUMBER);
    printf("<p>%s: %s</p> \n",
           _("Office Hours"),
           CONTACT_OFFICE_HOURS);
    printf("<p>(%s: %s - %s %s)</p>\n",
           _("Standard time zone"),
           CONTACT_TIME_ZONE,
           _("it is currently"), get_time_text());
    printf("<p>%s</p>\n",
           CONTACT_ADDRESS_ETC);
}

function freeze_file()
{
    return LOCK_DIR . "/FREEZE";
}

function set_frozen($freeze = true)
{
    if ($freeze) {
        $umask = umask(0);
        umask(0);
        if (!($fp = fopen(freeze_file(), "w")))
            throw new Error('Freeze Error', "Can't create freeze file " . freeze_file());
    } else {
        unlink(freeze_file());
        if (file_exists(freeze_file()))
            throw new Error('Unfreeze Error', "Can't unlink freeze file " . freeze_file());
    }
}

function is_frozen()
{
    return file_exists(freeze_file());
}

function check_frozen()
{
    if (is_frozen())
        throw new Error(_("Frozen"), _("Trading on the exchange is temporarily frozen"));
}

function sql_format_date($date)
{
    return "CONCAT(DATE_FORMAT($date, '%h:%i'), " .
                  "LOWER(DATE_FORMAT($date, '%p')), " .
                  "DATE_FORMAT($date, ' %d-%b-%y'))";
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

function find_total_trades_available_at_rate($rate, $have_curr)
{
    // find the total 'amount' and 'want_amount' in the book from people having $have_curr offering a rate of $rate or better (for us)
    do_query("SET div_precision_increment = 8");
    if ($have_curr == 'BTC')
        $query = "
    SELECT
        SUM(amount) AS amount,
        SUM(want_amount) as want_amount,
        MAX(initial_want_amount/initial_amount) as worst_price
    FROM
        orderbook
    WHERE
        type='BTC'
        AND status='OPEN'
        AND initial_want_amount/initial_amount <= $rate
        ";
    else
        $query = "
    SELECT
        SUM(amount) AS amount,
        SUM(want_amount) as want_amount,
        MIN(initial_amount/initial_want_amount) as worst_price
    FROM
        orderbook
    WHERE
        type='" . CURRENCY . "'
        AND status='OPEN'
        AND initial_amount/initial_want_amount >= $rate
        ";

    return mysql_fetch_array(do_query($query));
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

function get_login_status()
{
    if (!isset($_SESSION['uid']) || !isset($_SESSION['oidlogin']))
        return array(0, false);

    // just having a 'uid' in the session isn't enough to be logged in
    // check that the oidlogin matches the uid in case database has been reset
    $uid = $_SESSION['uid'];
    $oidlogin = $_SESSION['oidlogin'];

    $result = do_query("
        SELECT is_admin
        FROM users
        WHERE oidlogin = '$oidlogin'
        AND uid = '$uid'
    ");

    if (has_results($result)) {
        $row = mysql_fetch_array($result);
        $is_admin = $row['is_admin'];
        return array($uid, $is_admin == '1');
    }

    logout();
}

function get_openid_for_user($uid)
{
    $result = do_query("
        SELECT oidlogin
        FROM users
        WHERE uid = '$uid'
    ");

    if (!has_results($result))
        throw new Error("Unknown User", "User ID $uid isn't known");

    $row = mysql_fetch_array($result);
    return $row['oidlogin'];
}

// not used any more - call get_login_status() instead
// function is_logged_in()
// {
//     $status = get_login_status();
//     return $status[0];
// }

// not used any more - call get_login_status() instead
// function is_admin()
// {
//     $status = get_login_status();
//     return $status[1];
// }
    
function user_id()
{
    if (!isset($_SESSION['uid'])) {
        # grave error. This should never happen and should be reported as an urgent breach.
        throw new Error('Login 404', "You're not logged in. Proceed to the <a href='?login.php'>login</a> form.");
    }
    return $_SESSION['uid'];
}

function get_wait_lock($uid)
{
    $lock = LOCK_DIR . "/" . $uid . ".wait";

    $umask = umask(0);
    if (!($fp = fopen($lock, "w"))) {
        umask($umask);
        throw new Error(_('Lock Error'), "Can't create wait lockfile for $uid");
    }

    if (!flock($fp, LOCK_EX|LOCK_NB)) {
        umask($umask);
        return false;
    }

    umask($umask);
    return $fp;
}

function release_wait_lock($fp)
{
    flock($fp, LOCK_UN);
    fclose($fp);
}

$lock_count = array();
$lock_fp = array();

// $block = 0: fail instantly if already locked
// $block = 1: wait for lock if nobody else is already waiting, else fail instantly
// $block = 2: wait for lock, even if someone else is already waiting
function get_lock($uid, $block)
{
    global $lock_count, $lock_fp;

    if (array_key_exists($uid, $lock_count)) {
        $lock_count[$uid]++;
        return;
    }

    $lock = LOCK_DIR . "/" . $uid;

    $umask = umask(0);
    if (!($fp = fopen($lock, "w"))) {
        umask($umask);
        throw new Error(_('Lock Error'), "Can't create lockfile for $uid");
    }

    $block_flags = LOCK_EX;
    $no_block_flags = LOCK_EX|LOCK_NB;

    if ($block == 0) {
        if (!flock($fp, $no_block_flags)) {
            umask($umask);
            throw new Error(_('Lock Error'), sprintf(_("User %s is already doing stuff."), $uid) . "<br/>");
        }
    } else if ($block == 2) {
        // try to get wait_lock.  don't care whether we get it or not, only doing it to tell others who care that we're waiting
        $wait_lock = get_wait_lock($uid);
        if (!flock($fp, $block_flags)) {
            if ($wait_lock)
                release_wait_lock($wait_lock);
            umask($umask);
            throw new Error(_('Lock Error'), sprintf(_("Can't get lock for user %s, even after waiting."), $uid) . "<br/>");
        }
        if ($wait_lock)
            release_wait_lock($wait_lock);
    } else if ($block == 1) {
        // try getting the lock without blocking first
        if (!flock($fp, $no_block_flags)) {
            // if we can't, then check whether anyone else is already waiting
            $wait_lock = get_wait_lock($uid);
            if ($wait_lock) {
                // if not, then wait
                if (!flock($fp, $block_flags)) {
                    release_wait_lock($wait_lock);
                    umask($umask);
                    throw new Error(_('Lock Error'), sprintf(_("Can't get lock for user %s, even after waiting."), $uid) . "<br/>");
                }
                release_wait_lock($wait_lock);
            } else {
                umask($umask);
                throw new Error(_('Lock Error'), sprintf(_("User %s is already doing stuff, and also already waiting for lock."), $uid) . "<br/>");
            }
        }
    }

    umask($umask);

    $lock_count[$uid] = 1;
    $lock_fp[$uid] = $fp;

    return;
}

function get_lock_without_waiting($uid)
{
    get_lock($uid, 0);
}

function wait_for_lock_if_no_others_are_waiting($uid)
{
    get_lock($uid, 1);
}

function wait_for_lock($uid)
{
    get_lock($uid, 2);
}

function release_lock($uid)
{
    global $lock_count, $lock_fp;

    if (array_key_exists($uid, $lock_count)) {
        $lock_count[$uid]--;
        if ($lock_count[$uid] == 0) {
            $fp = $lock_fp[$uid];
            flock($fp, LOCK_UN);
            fclose($fp);
            unset($lock_count[$uid]);
            unset($lock_fp[$uid]);
        }
        return;
    }
    throw new Error('Unlock error', "lock for user $uid isn't held - can't release it");
}

function cleanup_string($val, $extra='')
{
    $val = preg_replace("/[^A-Za-z0-9 .$extra]/", '', $val);
    return mysql_real_escape_string($val);
}
function post($key, $extra='')
{
    if (!isset($_POST[$key]))
        throw new Error('Ooops!', "Missing posted value $key!");
    return cleanup_string($_POST[$key], $extra);
}
function get($key, $extra='')
{
    if (!isset($_GET[$key]))
        throw new Error('Ooops!', "Missing get value $key!");
    return cleanup_string($_GET[$key], $extra);
}

function sync_to_bitcoin($uid)
{
    if (!is_string($uid))
        throw new Error('Coding error!', "sync_to_bitcoin() expects a string, not type '" . gettype($uid) . "'");
        
    $bitcoin = connect_bitcoin();
    try {
        $balance = @$bitcoin->getbalance($uid, CONFIRMATIONS_FOR_DEPOSIT);

        if (is_float($balance))
            throw new Error(_("bitcoind version error"), _("bitcoind getbalance should return an integer not a float"));

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
    $balances = array(CURRENCY=>'0','BTC'=>'0');
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
    echo "You have ", internal_to_numstr($balances[CURRENCY]), " " . CURRENCY . " and ",
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

function get_last_price($precision = 8)
{
    $query = "
    SELECT
        a_amount,
        b_amount
    FROM
        transactions
    WHERE
        b_amount > 0
    ORDER BY
        timest DESC
    LIMIT 1
    ";
    $result = do_query($query);
    if (has_results($result)) {
        $row = get_row($result);
        $last = bcdiv($row['a_amount'], $row['b_amount'], $precision);
    }
    else
        $last = 0;

    return clean_sql_numstr($last);
}

// {"ticker":
//   {"high":11.89,             highest traded price in last 24h
//    "low":9.903,              lowest traded price in last 24h
//    "avg":10.743607598,       mean traded price in last 24h (sum of price / number of prices)
//    "vwap":10.844024918,      volume weighted average price (sum of price*amount / sum of amount)
//    "vol":49103,              volume (in BTC)
//    "last":11.35,             last traded price
//    "buy":11.35,              highest buy offer
//    "sell":11.38967           lowest sell offer
//   }
// }
//
// I'm doing the divisions using bcdiv() in PHP rather than in the SQL
// query because even with SET div_precision_increment = 4; it was
// giving 8 digits of precision:
//
// mysql> SELECT a_amount, b_amount, a_amount/b_amount, sum(a_amount/b_amount) as sum,
//               sum(a_amount/b_amount)/1 as sumover1
//        FROM transactions WHERE b_amount > 0 AND timest > NOW() - INTERVAL 1 DAY;
//
// +-----------+----------+-------------------+---------+-------------+
// | a_amount  | b_amount | a_amount/b_amount | sum     | sumover1    |
// +-----------+----------+-------------------+---------+-------------+
// | 500000000 | 22727272 |      22.000000704 | 22.0000 | 22.00000070 |
// +-----------+----------+-------------------+---------+-------------+

function get_ticker_data()
{
    $query = "
    SELECT
        MAX(a_amount/b_amount) AS high,
        MIN(a_amount/b_amount) AS low,
        SUM(a_amount/b_amount) AS sum_of_prices,
        COUNT(*)               AS number_of_prices,
        SUM(a_amount)          AS sum_of_a_amounts,
        SUM(b_amount)          AS sum_of_b_amounts,
        SUM(b_amount)          AS vol
    FROM
        transactions
    WHERE
        b_amount > 0
        AND timest > NOW() - INTERVAL 1 DAY;
    ";
    $result = do_query($query);
    $row = get_row($result);
    if (isset($row['vol'])) {
        $sum_of_prices = $row['sum_of_prices'];
        $number_of_prices = $row['number_of_prices'];
        $sum_of_a_amounts = $row['sum_of_a_amounts'];
        $sum_of_b_amounts = $row['sum_of_b_amounts'];

        $high = clean_sql_numstr($row['high']);
        $low  = clean_sql_numstr($row['low']);
        $avg  = clean_sql_numstr(bcdiv($sum_of_prices,    $number_of_prices, 4));
        $vwap = clean_sql_numstr(bcdiv($sum_of_a_amounts, $sum_of_b_amounts, 4));
        $vol  = internal_to_numstr($row['vol'], 4);
    } else
        $high = $low = $avg = $vwap = $vol = 0;

    $exchange_fields = calc_exchange_rate(CURRENCY, 'BTC', BASE_CURRENCY::B);
    if (!$exchange_fields)
        $buy = 0;
    else
        list($total_amount, $total_want_amount, $buy) = $exchange_fields; 

    $exchange_fields = calc_exchange_rate('BTC', CURRENCY, BASE_CURRENCY::A);
    if (!$exchange_fields)
        $sell = 0;
    else
        list($total_amount, $total_want_amount, $sell) = $exchange_fields; 

    $last = get_last_price(4);

    // for testing layout when all stats have 4 decimal places
    // return array('21.1234', '20.1234', '21.6789', '21.4567', '1234567.3456', '21.5543', '21.2345', '22.1257');
    return array($high, $low, $avg, $vwap, $vol, $last, $buy, $sell);
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

function active_table_cell_link_to_user_statement($user)
{
    $url = "?page=statement&user=$user";
    printf("<td class='active' %s %s %s>$user</td>",
           "onmouseover=\"style.backgroundColor='#8ae3bf';\"",
           "onmouseout=\"style.backgroundColor='#7ad3af';\"",
           "onclick=\"document.location='$url'\"");
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
            " . sql_format_date("timest") . " AS timest_format
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
    $supported_currencies = array(CURRENCY, 'BTC');
    if (!in_array($curr_type, $supported_currencies))
        throw new Error('Ooops!', 'Bad currency supplied.');
}
function order_worthwhile_check($amount, $amount_disp, $min_str='0.5')
{
    if (!is_numeric($amount_disp))
        throw new Problem(_('Numbers. Numbers.'), _('The value you entered was not a number.'));
    $min = numstr_to_internal($min_str);
    if ($amount < $min)
        throw new Problem(_("Try again..."), sprintf(_("Your order size is too small. The minimum is %s."), $min_str));
}
function enough_money_check($amount, $curr_type)
{
    if (!has_enough($amount, $curr_type))
        throw new Problem(sprintf(_("Where's the gold?", "You don't have enough %s."), $curr_type));
}

function translate_order_code($code)
{
    # OPEN CANCEL CLOSED
    switch ($code)
    {
        case 'OPEN':
            return _('Open');
        case 'CANCEL':
            return _('Cancelled');
        case 'CLOSED':
            return _('Completed');
        default:
            throw new Error(_('No such order'), _('This order is wrong...'));
    }
}

function translate_request_type($type)
{
    switch ($type)
    {
        case 'WITHDR':
            return _('Withdraw');
        case 'DEPOS':
            return _('Deposit');
        default:
            throw new Error(_('No such request type'), _('This request is wrong...'));
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
            return _('Verifying');
        case 'PROCES':
            return _('Processing');
        case 'FINAL':
            return _('Finished');
        case 'IGNORE':
            return _('Ignored');
        case 'REJECT':
            return _('Rejected');
        case 'CANCEL':
            return _('Cancelled');
        default:
            throw new Error(_('No such request'), _('This request is wrong...'));
    }
}

function get_time_text()
{
    // see http://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format
    $result = do_query("SELECT CONCAT(DATE_FORMAT(now, '%l:%i')," .
                       "LOWER(DATE_FORMAT(now, '%p'))," .
                       "DATE_FORMAT(now, ' on %W %D')) AS time" .
                       " FROM (SELECT NOW() AS now) now");
    $row = mysql_fetch_assoc($result);
    return $row['time'];
}

function show_commission_rates()
{
    echo "<blockquote>\n";

    $cap = COMMISSION_CAP_IN_BTC;
    $rate = COMMISSION_PERCENTAGE_FOR_BTC;
    if ($rate == 0)
        printf("<p>" . _("buying %s is free of commission") . "</p>\n", "BTC");
    else {
        echo "<p>$rate%";
        if ($cap)
            echo " (" . _("capped at") . " " . btc_to_numstr($cap) . ")";
        else
            echo " (" . _("uncapped") . ")";
        echo " " . _("when buying BTC") . "</p>\n";
    }

    $cap = COMMISSION_CAP_IN_FIAT;
    $rate = COMMISSION_PERCENTAGE_FOR_FIAT;
    if ($rate == 0)
        printf("<p>" . _("buying %s is free of commission") . "</p>\n", CURRENCY);
    else {
        echo "<p>$rate%";
        if ($cap)
            echo " (" . _("capped at") . " " . fiat_to_numstr($cap) . ")";
        else
            echo " (" . _("uncapped") . ")";
        echo " " . _("when selling BTC") . "</p>\n";
    }

    $cap = COMMISSION_CAP_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER;
    $rate = COMMISSION_PERCENTAGE_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER;
    $item = sprintf("MtGox %s vouchers", CURRENCY);
    if ($rate == 0)
        printf("<p>" . _("depositing %s is free of commission") . "</p>\n", $item);
    else {
        echo "<p>$rate%";
        if ($cap)
            echo " (" . _("capped at") . " " . fiat_to_numstr($cap) . ")";
        else
            echo " (" . _("uncapped") . ")";
        echo " " . sprintf(_("when depositing %s"), $item) . "</p>\n";
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

function commission($amount, $percentage, $cap = false, $already_paid = '0')
{
    $commission = gmp_div(gmp_mul((string)$amount,
                                  numstr_to_internal((string)$percentage)),
                          numstr_to_internal(100));

    // reduce the cap by the amount we already paid, but no lower than 0
    if (!$cap) return gmp_strval($commission);

    $cap = max(gmp_strval(gmp_sub(numstr_to_internal($cap), $already_paid)), '0');
    return min(gmp_strval($commission), $cap);
}

function commission_on_fiat($fiat, $already_paid)
{
    return commission($fiat,
                      COMMISSION_PERCENTAGE_FOR_FIAT,
                      COMMISSION_CAP_IN_FIAT,
                      $already_paid);
}

function commission_on_btc($btc, $already_paid)
{
    return commission($btc,
                      COMMISSION_PERCENTAGE_FOR_BTC,
                      COMMISSION_CAP_IN_BTC,
                      $already_paid);
}

function commission_on_deposit_mtgox_fiat_voucher($amount)
{
    return commission($amount,
                      COMMISSION_PERCENTAGE_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER,
                      COMMISSION_CAP_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER);
}

// function commission_on_withdraw_mtgox_fiat_voucher($amount)
// {
//     return commission($amount,
//                       COMMISSION_PERCENTAGE_FOR_WITHDRAW_MTGOX_FIAT_VOUCHER,
//                       COMMISSION_CAP_FOR_WITHDRAW_MTGOX_FIAT_VOUCHER);
// }

// calculate and return the commission to pay on $amount of type $curr_type if $already_paid has already been paid on this order
function commission_on_type($amount, $curr_type, $already_paid)
{
    if ($curr_type == CURRENCY)
        return commission_on_fiat($amount, $already_paid);

    if ($curr_type == 'BTC')
        return commission_on_btc($amount, $already_paid);

    throw new Error('Unknown currency type', "Type $curr_type isn't " . CURRENCY . " or BTC");
}

function day_time_range_string()
{
    $offset = DAY_STARTS_MINUTES_AFTER_MIDNIGHT;
    return minutes_past_midnight_as_time_string($offset) . " to " . minutes_past_midnight_as_time_string($offset-1);
}

function minutes_past_midnight_as_time_string($minutes)
{
    if ($minutes == 0)
        return _("midnight");

    if ($minutes == 12*60)
        return _("noon");

    return str_replace(' ', '', strftime("%l:%M%P", mktime(0,0,0) + $minutes*60));
}

// sum available and committed FIAT amounts
function total_fiat_balance($uid)
{
    $balances = fetch_balances($uid);
    $committed_balances = fetch_committed_balances($uid);
    $total_fiat_balance = gmp_add($balances[CURRENCY], $committed_balances[CURRENCY]);
    return $total_fiat_balance;
}

function fiat_transferred_today($uid)
{
    $midnight_offset = DAY_STARTS_MINUTES_AFTER_MIDNIGHT;
    $query = "
        SELECT SUM(amount) as sum
        FROM requests
        WHERE timest > (SELECT IF (NOW() > CURRENT_DATE + INTERVAL $midnight_offset MINUTE,
                                   CURRENT_DATE,
                                   CURRENT_DATE - INTERVAL 1 DAY))
                     + INTERVAL $midnight_offset MINUTE
        AND uid = $uid
        AND req_type in ('WITHDR', 'DEPOS')
        AND curr_type = '" . CURRENCY . "'
        AND status != 'CANCEL'
    ";
    $result = do_query($query);
    $row = get_row($result);
    $sum = $row['sum'];
    if (!$sum) $sum = '0';
    return $sum;
}

function btc_withdrawn_today($uid)
{
    $midnight_offset = DAY_STARTS_MINUTES_AFTER_MIDNIGHT;
    $query = "
        SELECT SUM(amount) as sum
        FROM requests
        WHERE timest > (SELECT IF (NOW() > CURRENT_DATE + INTERVAL $midnight_offset MINUTE,
                                   CURRENT_DATE,
                                   CURRENT_DATE - INTERVAL 1 DAY))
                     + INTERVAL $midnight_offset MINUTE
        AND uid = $uid
        AND req_type = 'WITHDR'
        AND curr_type = 'BTC'
        AND status != 'CANCEL'
    ";
    $result = do_query($query);
    $row = get_row($result);
    $sum = $row['sum'];
    if (!$sum) $sum = '0';
    return $sum;
}

function check_fiat_balance_limit($uid, $amount)
{
    $balance = total_fiat_balance($uid);
    $limit = numstr_to_internal(MAXIMUM_FIAT_BALANCE);
    printf("<p>" . _("Maximum balance is %s and you have %s") . "</p>\n",
           internal_to_numstr($limit)   . " " . CURRENCY,
           internal_to_numstr($balance) . " " . CURRENCY);
}

function check_fiat_transfer_limit($uid, $amount)
{
    $withdrawn = fiat_transferred_today($uid);
    $limit = numstr_to_internal(MAXIMUM_DAILY_FIAT_TRANSFER);
    $available = gmp_sub($limit, $withdrawn);

    if (gmp_cmp($amount, $available) > 0)
        throw new Problem(_('Daily limit exceeded'), sprintf(_('You can only transfer %s per day.'),
                                                             internal_to_numstr($limit) . ' ' . CURRENCY));
}

function check_btc_withdraw_limit($uid, $amount)
{
    $withdrawn = btc_withdrawn_today($uid);
    $limit = numstr_to_internal(MAXIMUM_DAILY_BTC_WITHDRAW);
    $available = gmp_sub($limit, $withdrawn);

    if (gmp_cmp($amount, $available) > 0)
        throw new Problem(_('Daily limit exceeded'), sprintf(_('You can only withdraw %s per day.'),
                                                             internal_to_numstr($limit) . ' BTC'));
}

function check_withdraw_limit($uid, $amount, $curr_type)
{
    if ($curr_type == 'BTC')
        check_btc_withdraw_limit($uid, $amount);
    else
        check_fiat_transfer_limit($uid, $amount);
}

function addlog($text)
{
    global $is_logged_in;

    $text = sprintf("%s %4s %s\n", date('r'), $is_logged_in, $text);

    wait_for_lock('log');
    if ($fp = @fopen(LOGFILE, 'a')) {
        fwrite($fp, $text);
        fclose($fp);
    }
    release_lock('log');
}

?>
