<?php
require_once "../config.php";
require_once ABSPATH . "/withdraw_utils.php";

function withdrawFiat()
{
    check_verified();

    $_POST['is_international'] = 0;
    unset($_POST['voucher']);

    do_withdraw(post('amount'), CURRENCY, $voucher_code, $reqid);
    
    return array("status"  => "OK",
                 "reqid"   => $reqid);
}

process_api_request("withdrawFiat", "withdraw");

?>
