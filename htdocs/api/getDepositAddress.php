<?php
require_once "../config.php";
require_once ABSPATH . "/util.php";

function main()
{
    global $is_logged_in;

    $address = connect_bitcoin()->getaccountaddress($is_logged_in);

    return array("status"  => "OK",
                 "address" => $address);
}

process_api_request("main", "read");

?>
