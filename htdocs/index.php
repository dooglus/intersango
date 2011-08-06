<?php

// this will be used to protect all subpages from being directly accessed.
define('_we_are_one', 1);
session_start();

require_once 'config.php';

function logout()
{
    session_destroy();

    // expire the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 36*60*60, $params["path"],   $params["domain"], $params["secure"], $params["httponly"]);
    }
    header('Location: .');
    exit();
}

// change the session ID regularly
if (!isset($_SESSION['creation_time'])) {
    $_SESSION['creation_time'] = time();
} else if (time() - $_SESSION['creation_time'] > max_session_id_lifetime() * 60) {
    session_regenerate_id(true);
    $_SESSION['creation_time'] = time();
}

// log the user out if they're idle too long
if (isset($_SESSION['uid']) && isset($_SESSION['last_activity'])) {
    $inactivity = time() - $_SESSION['last_activity'];
    if ($inactivity > max_idle_minutes_before_logout() * 60)
        logout();
}
$_SESSION['last_activity'] = time();

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

if($page == 'logout')
    logout();

// turn output buffering on
ob_start();

require_once "$abspath/header.php";
require_once "$abspath/util.php";
include "$abspath/switcher.php";

switcher($page, is_logged_in());

// debugging for session stuff
if (0) {
    echo "<div class='content_box'>\n";
    echo "<h3>Debug</h3>\n";
    echo "<p>\n";
    echo "session id: ", session_id(), "<br/>\n";
    echo "session age: ", time() - $_SESSION['creation_time'], " seconds<br/>\n";
    if (isset($inactivity)) echo "you were inactive for $inactivity seconds<br/>\n";
    echo "max_idle_minutes_before_logout() = ", max_idle_minutes_before_logout(), " minutes = ", max_idle_minutes_before_logout() * 60, " seconds<br/>\n";
    echo "max_session_id_lifetime() = ", max_session_id_lifetime(), " minutes = ", max_session_id_lifetime() * 60, " seconds<br/>\n";
    echo "</p></div>\n";
}

// actually re-checks whether you're logged in or not because
// switcher() can log you in and set $_SESSION there
require_once "$abspath/footer.php";

// send the contents of the output buffer
ob_end_flush();
?>
