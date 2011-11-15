<?php
require_once "../config.php";
require_once ABSPATH . "/withdraw_utils.php";

function main()
{
    $_POST['is_international'] = 0;
    unset($_POST['voucher']);
    $_POST['currency'] = CURRENCY;

    do_withdraw(post('amount'), post('currency'), $voucher_code, $reqid);
    
    return array("status"  => "OK",
                 "reqid"   => $reqid);
}

process_api_request("main", "withdraw");

?>
