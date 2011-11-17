<?php
require_once "../config.php";
require_once ABSPATH . "/order_utils.php";

function getOrders()
{
    global $is_logged_in;

    $orders = get_orders();

    return array("status"  => "OK",
                 "orders"  => $orders);
}

process_api_request("getOrders", "read");

?>
