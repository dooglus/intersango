<?php

function enable_errors()
{
    error_reporting(E_ALL|E_STRICT);
    ini_set('display_errors', '1');
}
function disable_errors_if_not_me()
{
    if ($_SERVER["REMOTE_ADDR"] != "127.0.0.1") {
        error_reporting(-1);
        ini_set('display_errors', '0');
    }
}

enable_errors();
$abspath = '/home/genjix/src/intersango';
$bitcoin_disabled = true;
?>

