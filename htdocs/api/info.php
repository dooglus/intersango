<?php
require_once "../config.php";
require_once ABSPATH . "/util.php";

function info()
{
    global $is_logged_in;

    $balances = fetch_balances($is_logged_in);
    
    return array("status"  => "OK",
                 "uid"     => $is_logged_in,
                 "BTC"     => internal_to_numstr($balances['BTC']),
                 CURRENCY  => internal_to_numstr($balances[CURRENCY]));
}

process_api_request("info", "read");

?>
