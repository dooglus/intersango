<?php
require_once "../config.php";
require_once ABSPATH . "/order_utils.php";

function addOrder()
{
    $reqid = place_order(post('have_amount'), post('have_currency'),
                         post('want_amount'), post('want_currency'));

    return array("status"  => "OK",
                 "orderid" => $reqid);
}

process_api_request("addOrder", "trade");

?>
