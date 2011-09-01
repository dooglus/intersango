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

    echo "code: $code<br/>\n";
    if (VOUCHER_FORCE_UPPERCASE)
        $code = strtoupper($code);
    echo "code: $code<br/>\n";
    $code = str_replace($from, $to, $code);
    echo "code: $code<br/>\n";
    $query = "SELECT reqid FROM voucher_requests WHERE voucher = '$code'";
    $result = do_query($query);
    if (!has_results($result))
        return false;

    $row = get_row($result);
    return $row['reqid'];
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

function store_new_bitcoin_voucher_code($reqid)
{
    return store_new_voucher_code($reqid, 'BTC');
}

function store_new_fiat_voucher_code($reqid)
{
    return store_new_voucher_code($reqid, 'AUD');
}

check_voucher_code("0123-GHIJKLMNOPQRST-0oOqQ-1iI");

?>
