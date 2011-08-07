<?php
# security protection
defined('_we_are_one') || die('Direct access not allowed.');

function switcher($page, $loggedin)
{
    try {
        switch($page) {
            case 'deposit':
            case 'place_order':
            case 'profile':
            case 'turn_on_duo':
            case 'view_order':
            case 'view_request':
            case 'withdraw':
                if ($loggedin)
                    include("$page.php");
                else
                    log_badpage($page);
                break;

            case 'login':
                if (!$loggedin)
                    include("login.php");
                else
                    log_badpage($page);
                break;

            case '404':
            case 'help':
            case 'orderbook':
            case 'test':
            case 'trade':
                include("$page.php");
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
