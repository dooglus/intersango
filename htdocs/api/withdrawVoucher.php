<?php
require_once "../config.php";
require_once ABSPATH . "/withdraw_utils.php";

function withdrawVoucher()
{
    check_verified();

    $_POST['is_international'] = 0;
    $_POST['voucher'] = 1;

    do_withdraw(post('amount'), post('currency'), $voucher_code, $reqid);
    
    return array("status"  => "OK",
                 "voucher" => $voucher_code,
                 "reqid"   => $reqid);
}

process_api_request("withdrawVoucher", "withdraw");

?>
