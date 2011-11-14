<?php
require_once "../config.php";
require_once ABSPATH . "/voucher.php";

function main()
{
    global $is_logged_in;

    $voucher = post('voucher', '-');
    try {
        get_lock("redeem_voucher", 2);
        list ($currency, $amount) = redeem_voucher($voucher, $is_logged_in);
        release_lock("redeem_voucher");
    } catch (Exception $e) {
        release_lock("redeem_voucher");
        throw new Exception($e->getMessage());
    }
    
    return array("status"   => "OK",
                 "currency" => $currency,
                 "amount"   => internal_to_numstr($amount));
}

process_api_request("main", "deposit");

?>
