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

if (isset($_GET['page']))
    $page = htmlspecialchars($_GET['page']);
else
    $page = 'trade';

// if the user has been logged in but is idle, log them out unless this is just an ajax request, in which case just act as if they're not logged in
if (isset($_SESSION['uid']) &&
    isset($_SESSION['last_activity']) &&
    time() - $_SESSION['last_activity'] > MAX_IDLE_MINUTES_BEFORE_LOGOUT * 60 &&
    !isset($_GET['fancy']))
    if (isset($_COOKIE['openid']) && isset($_COOKIE['autologin']) &&
        count($_POST) == 0) {
        if ($page != 'login') {
            if ($_SERVER['QUERY_STRING'])
                $next_page = "?" . $_SERVER['QUERY_STRING'];
            else
                $next_page = "?page=$page";
            require_once ABSPATH . "/login.php";
            show_footer(0, false, false);
            exit;
        }
    } else
        logout();                   // this exit()s
else {
    $_SESSION['last_activity'] = time();
    get_login_status();
}

if(!isset($_SESSION['csrf_token']))
{
    $_SESSION['csrf_token'] = '';
    for($i=0;$i<32;$i++)
    {
        $_SESSION['csrf_token'] .= bin2hex(chr(mt_rand(0,255)));
    }   
}

switcher($page, $is_logged_in, $is_admin);

// send the contents of the output buffer
ob_end_flush();
?>
