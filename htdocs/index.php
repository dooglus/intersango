<?php

// this will be used to protect all subpages from being directly accessed.
define('_we_are_one', 1);
session_start();

// turn output buffering on
ob_start();

require_once "config.php";
require_once ABSPATH . "/util.php";
require_once ABSPATH . "/localization.php";
require_once ABSPATH . "/header.php";
require_once ABSPATH . "/switcher.php";
require_once ABSPATH . "/footer.php";

// change the session ID regularly
if (!isset($_SESSION['creation_time'])) {
    $_SESSION['creation_time'] = time();
} else if (time() - $_SESSION['creation_time'] > MAX_SESSION_ID_LIFETIME * 60) {
    session_regenerate_id(true);
    $_SESSION['creation_time'] = time();
}

// log the user out if they're idle too long
if (isset($_SESSION['uid']) && isset($_SESSION['last_activity'])) {
    $inactivity = time() - $_SESSION['last_activity'];
    if ($inactivity > MAX_IDLE_MINUTES_BEFORE_LOGOUT * 60)
        logout();
}
$_SESSION['last_activity'] = time();

date_default_timezone_set(TIMEZONE);

if(!isset($_SESSION['csrf_token']))
{
    $_SESSION['csrf_token'] = '';
    for($i=0;$i<32;$i++)
    {
        $_SESSION['csrf_token'] .= bin2hex(chr(mt_rand(0,255)));
    }   
}

if (isset($_GET['page']))
    $page = htmlspecialchars($_GET['page']);
else
    $page = 'trade';

list ($is_logged_in, $is_admin) = get_login_status();

switcher($page, $is_logged_in, $is_admin);

// send the contents of the output buffer
ob_end_flush();
?>
