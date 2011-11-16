<?php
require_once "../config.php";
require_once ABSPATH . "/withdraw_utils.php";

function main()
{
    $_POST['is_international'] = 0;
    unset($_POST['voucher']);

    do_withdraw(post('amount'), "BTC", $voucher_code, $reqid);
    
    return array("status"  => "OK",
                 "reqid"   => $reqid);
}

process_api_request("main", "withdraw");

?>
