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

function voucher_code($type)
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

    return $code;
}

function bitcoin_voucher_code()
{
    return voucher_code('BTC');
}

function fiat_voucher_code()
{
    return voucher_code('AUD');
}

?>
