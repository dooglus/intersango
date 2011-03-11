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
    try {
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
            case 'orderbook':
                include("$abspath/orderbook.php");
                break;
            case 'place_order':
                if ($loggedin)
                    include("$abspath/place_order.php");
                else
                    report();
                break;
            case '':
                include("$abspath/index.php");
                break;  
            default:
                report();
                break;
        }
    } 
    catch (Error $e) {
        # Same as below, but flag + log this for review,
        echo "<div class='content_box'><h3>{$e->getTitle()}</h3>";
        echo "<p>{$e->getMessage()}</p></div>";
    }
    catch (Problem $e) {
        echo "<div class='content_box'><h3>{$e->getTitle()}</h3>";
        echo "<p>{$e->getMessage()}</p></div>";
    }
}

?>

