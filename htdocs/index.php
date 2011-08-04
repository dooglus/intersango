<?php
# this will be used to protect all subpages from being directly accessed.
define('_we_are_one', 1);
session_start();

if(!isset($_SESSION['csrf_token']))
{
    $_SESSION['csrf_token'] = '';
    for($i=0;$i<32;$i++)
    {
        $_SESSION['csrf_token'] .= bin2hex(chr(mt_rand(0,255)));
    }   
}

require_once 'config.php';

if (isset($_GET['page']))
    $page = htmlspecialchars($_GET['page']);
else
    $page = '';

if($page == 'logout') {
  session_destroy();
  header('Location: ?page=trade');
  exit();
}
else {
    ob_start();
    require 'header.php';
    include "$abspath/switcher.php";
    if (isset($_SESSION['uid']) && $_SESSION['uid'])
        switcher($page, true);
    else
        switcher($page, false);
    # actually re-checks whether you're logged in or not because
    # switcher() can log you in and set $_SESSION there
    require 'footer.php';
    ob_end_flush();
}
?>
