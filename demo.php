<?php

require_once "wbx_api.php";

// get these from https://www.worldbitcoinexchange.com/?page=api
define('API_KEY'   , '00000000-00000000-00000000-00000000-00000000');
define('API_SECRET', '00000000-00000000-00000000-00000000-00000000-00000000-00000000-00000000');

$wbx = new WBX_API(API_KEY, API_SECRET);

var_dump($wbx->info());
var_dump($wbx->get_deposit_address());
var_dump($wbx->cancel_order(12345));

?>
