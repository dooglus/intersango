<?php
# security protection
defined('_we_are_one') || die('Direct access not allowed.');

function switcher($page, $loggedin)
{
    try {
        switch($page) {
            case 'profile':
                if ($loggedin)
                    include("profile.php");
                else
                    log_badpage($page);
                break;

            case 'view_order':
                if ($loggedin)
                    include("view_order.php");
                else
                    log_badpage($page);
                break;

            case 'view_request':
                if ($loggedin)
                    include("view_request.php");
                else
                    log_badpage($page);
                break;

            case 'login':
                if (!$loggedin)
                    include("login.php");
                else
                    log_badpage($page);
                break;

            case 'deposit':
                include("deposit.php");
                break;

            case 'withdraw':
                include("withdraw.php");
                break;

            case 'help':
                include("help.php");
                break;

            case 'orderbook':
                include("orderbook.php");
                break;

            case 'place_order':
                if ($loggedin)
                    include("place_order.php");
                else
                    log_badpage($page);
                break;

            case 'turn_on_duo':
                if ($loggedin)
                    include("turn_on_duo.php");
                else
                    log_badpage($page);
                break;

            case '404':
                include("404.php");
                break;  

            case 'test':
                include("test.php");
                break;  

            case 'trade':
                include("trade.php");
                break;  

            default:
                log_badpage($page);
                break;
        }
    } 
    catch (Error $e) {
        report_exception($e, SEVERITY::ERROR);
        # Same as below, but flag + log this for review,
        echo "<div class='content_box'><h3>{$e->getTitle()}</h3>";
        echo "<p>{$e->getMessage()}</p></div>";
    }
    catch (Problem $e) {
        echo "<div class='content_box'><h3>{$e->getTitle()}</h3>";
        echo "<p>{$e->getMessage()}</p></div>";
    }
    catch (Exception $e) {
        echo "<div class='content_box'><h3>Technical difficulties</h3>";
        echo "<p>{$e->getMessage()}</p></div>";
    }
}
?>
