<?php
# this will be used to protect all subpages from being directly accessed.
define('_we_are_one', 1);
session_start();

$abspath = dirname(dirname(__FILE__));
require "$abspath/errors.php";
enable_errors();

if (isset($_GET['page']))
    $page = htmlspecialchars($_GET['page']);
else
    $page = '';

if($page == 'logout') {
  session_destroy();
  header('Location: index.php');
  exit();
}
else {
    require 'header.php';
    include "$abspath/switcher.php";
    if (isset($_SESSION['uid']) && $_SESSION['uid'])
        switcher($page, true);
    else
        switcher($page, false);
    # actually re-checks whether you're logged in or not because
    # switcher() can log you in and set $_SESSION there
    require 'footer.php';
}
?>

