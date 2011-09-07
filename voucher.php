<?php

require_once "db.php";
require_once "util.php";
require_once "mtgox_api.php";

function random_voucher_string($len)
{
    $voucher_chars_str = VOUCHER_CHARS;
    $voucher_chars_length = strlen(VOUCHER_CHARS);

    if (@is_readable('/dev/urandom')) {
        $fp = fopen('/dev/urandom', 'r');
        $urandom = fread($fp, $len);
        fclose($fp);
    }

    $return='';

    for ($i = 0; $i < $len; $i++) {
        if (!isset($urandom)) {
            echo "no urandom?\n";
            if ($i % 2 == 0)
                mt_srand(time() % 2147 * 1000000 + (double)microtime() * 1000000);
            $rand = mt_rand() % $voucher_chars_length;
        } else
            $rand = ord($urandom[$i]) % $voucher_chars_length;

        $return .= $voucher_chars_str[$rand];
    }

    return $return;
}

function voucher_code_prefix($code)
{
    $length = strlen(VOUCHER_PREFIX . "-BTC-XXXXX");

    return substr($code, 0, $length);
}

function encrypt_voucher_code($code, $salt)
{
    $hash = hash('sha256', $salt . $code);
    return $hash;
}

function voucher_code_exists($code)
{
    $prefix = voucher_code_prefix($code);

    $query = "
        SELECT reqid, redeem_reqid, salt, hash, uid, amount, curr_type, status
        FROM voucher_requests
        JOIN requests
        USING(reqid)
        WHERE prefix = '$prefix'
    ";

    $result = do_query($query);

    while ($row = mysql_fetch_array($result))
        if (encrypt_voucher_code($code, $row['salt']) == $row['hash'])
            return $row;

    return false;
}

function check_voucher_code($code)
{
    $from = array();
    $to = array();
    foreach (explode(',', VOUCHER_REPLACE) as $pair) {
        array_push($from, $pair[0]);
        array_push($to  , $pair[1]);
    }

    if (VOUCHER_FORCE_UPPERCASE)
        $code = strtoupper($code);
    $code = str_replace($from, $to, $code);

    $row = voucher_code_exists($code);
    if (!$row)
        throw new Exception("no such voucher exists");

    $reqid = $row['reqid'];
    $redeem_reqid = $row['redeem_reqid'];
    $uid = $row['uid'];
    $amount = $row['amount'];
    $curr_type = $row['curr_type'];
    $status = $row['status'];

    // echo "reqid: '$reqid'; redeem_reqid: '$redeem_reqid'; uid: $uid; amount: $amount; curr_type: $curr_type; status: $status<br/>\n";

    if ($redeem_reqid)
        throw new Exception("this voucher has already been redeemed");

    if ($status == 'CANCEL')
        throw new Exception("this voucher has been cancelled by the user who issued it");

    if ($status != 'VERIFY')
        throw new Exception("coding error; voucher wasn't redeemed or cancelled, but isn't in state 'VERIFY'");

    return array($reqid, $uid, $amount, $curr_type);
}

function random_voucher_salt()
{
    return random_voucher_string(5);
}

function store_new_voucher_code($reqid, $type)
{
    // $nonce = 0;

    do {
        $code = sprintf("%s-%s-%s-%s-%s-%s",
                        VOUCHER_PREFIX,
                        $type,
                        random_voucher_string(5),
                        random_voucher_string(5),
                        random_voucher_string(5),
                        random_voucher_string(5));

        // $code = sprintf("%s-%s-TEST1-00000-00000-%05d", VOUCHER_PREFIX, $type, $nonce++);
    } while (voucher_code_exists($code));

    $prefix = voucher_code_prefix($code);
    $salt = random_voucher_salt();
    $hash = encrypt_voucher_code($code, $salt);

    $query = "
        INSERT INTO voucher_requests (reqid, prefix, salt, hash)
        VALUES ('$reqid', '$prefix', '$salt', '$hash');
    ";
    do_query($query);

    return $code;
}

function redeemed_voucher_code($issuing_reqid, $redeeming_reqid)
{
    $query = "
        UPDATE voucher_requests
        SET redeem_reqid = $redeeming_reqid
        WHERE reqid = $issuing_reqid
    ";
    do_query($query);

    $query = "
        UPDATE requests
        SET status = 'FINAL'
        WHERE reqid = $issuing_reqid
    ";
    do_query($query);
}

function looks_like_mtgox_aud_voucher($code)
{
    return substr($code, 0, 10) == "MTGOX-AUD-";
}

function redeem_mtgox_aud_voucher($code, $uid)
{
    $mtgox = new MtGox_API(MTGOX_KEY, MTGOX_SECRET);

    $result = $mtgox->deposit_coupon($code);
    // echo "result: <pre>" . var_dump($result) . "</pre><br/>\n";

    // successful coupon deposit:
    //
    // array(4) {
    //   ["amount"]=>  float(0.01)
    //   ["currency"]=>  string(3) "BTC"
    //   ["reference"]=>  string(36) "beabf9ce-07b6-4852-ae71-4cfc671ff35d"
    //   ["status"]=>     string(49) "Your account has been credited by 0.01000000 BTC"
    // }

    // trying to redeem an already-spent code - note no 'status':
    //
    // array(1) {
    //   ["error"]=>  string(59) "This code cannot be redeemed (non existing or already used)"
    // }

    if (isset($result['error']))
        throw new Exception($result['error']);

    $amount = numstr_to_internal(cleanup_string($result['amount']));
    $curr_type = cleanup_string($result['currency']);
    // $reference = cleanup_string($result['reference'], '-');
    $status = cleanup_string($result['status']);

    // echo "<p>When we tried to redeem that voucher into our account, MtGox said: <strong>$status</strong></p>\n";
    $commission = commission_on_mtgox_voucher($amount);
    $amount = gmp_strval(gmp_sub($amount, $commission));

    $query = "
        INSERT INTO requests (req_type, uid, amount, commission, curr_type, status)
        VALUES ('DEPOS', '$uid', '$amount', '$commission', '$curr_type', 'FINAL');
    ";
    do_query($query);

    add_funds(1,    $commission, $curr_type);
    add_funds($uid, $amount,     $curr_type);

    echo "<p><strong>", internal_to_numstr($amount), " $curr_type has been credited to your account.</strong></p>\n";
}

function redeem_voucher($code, $uid)
{
    if (looks_like_mtgox_aud_voucher($code))
        return redeem_mtgox_aud_voucher($code, $uid);

    list($issuing_reqid, $issuing_uid, $amount, $curr_type) = check_voucher_code($code);
    // echo "issued in request $issuing_reqid by user $issuing_uid for amount $amount of $curr_type<br/>\n";

    $query = "
        INSERT INTO requests (req_type, uid, amount, curr_type, status)
        VALUES ('DEPOS', '$uid', '$amount', '$curr_type', 'FINAL');
    ";
    do_query($query);
    $reqid = mysql_insert_id();

    redeemed_voucher_code($issuing_reqid, $reqid);
    add_funds($uid, $amount, $curr_type);

    echo "<p><strong>", internal_to_numstr($amount), " $curr_type has been credited to your account.</strong></p>\n";
}

function store_new_bitcoin_voucher_code($reqid)
{
    return store_new_voucher_code($reqid, 'BTC');
}

function store_new_fiat_voucher_code($reqid)
{
    return store_new_voucher_code($reqid, 'AUD');
}

?>
