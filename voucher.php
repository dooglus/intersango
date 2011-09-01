<?php

require_once "db.php";
require_once "util.php";

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
    $query = "SELECT reqid, redeem_reqid, uid, amount, curr_type, status FROM voucher_requests JOIN requests USING(reqid) WHERE voucher = '$code'";
    $result = do_query($query);
    if (!has_results($result))
        throw new Exception("no such voucher exists");

    $row = get_row($result);
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

function store_new_voucher_code($reqid, $type)
{
    do {
        $code = sprintf("%s-%s-%s-%s-%s-%s",
                        VOUCHER_PREFIX,
                        $type,
                        random_voucher_string(5),
                        random_voucher_string(5),
                        random_voucher_string(5),
                        random_voucher_string(5));
        $query = "
            SELECT COUNT(*) AS count FROM voucher_requests
            WHERE voucher = '$code'
        ";
        $result = do_query($query);
        $row = mysql_fetch_array($result);
        $count = $row['count'];
    } while ($count == 1);

    $query = "
        INSERT INTO voucher_requests (reqid, voucher)
        VALUES ('$reqid', '$code');
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

function redeem_voucher($code, $uid)
{
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
