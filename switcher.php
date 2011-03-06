<?php
# security protection
defined('_we_are_one') || die('Direct access not allowed.');

function report()
{
    echo 'Reported';
}

function switcher($page, $loggedin)
{
    global $abspath;
    switch($page) {
        case 'profile':
            if ($loggedin)
                include("$abspath/profile.php");
            else
                report();
            break;
        case 'login':
            if (!$loggedin)
                # first force logout
                include("$abspath/login.php");
            else
                report();
            break;
        case 'show_orders':
            include("$abspath/show_orders.php");
            break;
        case 'place_order':
            if ($loggedin)
                include("$abspath/place_order.php");
            else
                report();
        case '':
            include("$abspath/index.php");
            break;  
        default:
            report();
            break;
    } 
}

?>

