<?php
# security protection
defined('_we_are_one') || die('Direct access not allowed.');

function switcher($page)
{
    global $is_logged_in, $is_admin;

    try {
        // delay showing the header when logging in until we know whether the login worked or not
        if ($page != 'login')
            show_header($page, $is_logged_in);

        $lock = false;
        if ($is_logged_in) $lock = get_lock($is_logged_in, 1); // wait if nobody else is waiting, else throw error

        switch($page) {

            ////////////////////////////////////////////////////////////////////////
            // for general consumption
            ////////////////////////////////////////////////////////////////////////
            case '404':
            case 'help':
            case 'orderbook':
            case 'test':
            case 'trade':
            case 'view_trades':
                include("$page.php");
                break;  

            ////////////////////////////////////////////////////////////////////////
            // for logged in users only
            ////////////////////////////////////////////////////////////////////////
            case 'deposit':
            case 'place_order':
            case 'profile':
            case 'turn_on_duo':
            case 'view_order':
            case 'view_request':
            case 'withdraw':
                if ($is_logged_in)
                    include("$page.php");
                else
                    log_badpage($page);
                break;

            ////////////////////////////////////////////////////////////////////////
            // for admin only
            ////////////////////////////////////////////////////////////////////////
            case 'bank':
            case 'commission':
            case 'add_cash':
            case 'freeze':
            case 'users':
                if ($is_admin)
                    include("$page.php");
                else
                    log_badpage($page);
                break;  

            case 'login':
                if (!$is_logged_in) {
                    include("login.php");

                    // we just tried to log in, so check whether or not it worked before showing the footer
                    list($is_logged_in, $is_admin) = get_login_status();
                }
                else
                    log_badpage($page);
                break;

            default:
                log_badpage($page);
                break;
        }

        // debugging for session stuff
        if (0) {
            echo "<div class='content_box'>\n";
            echo "<h3>Debug</h3>\n";
            echo "<p>\n";
            echo "session id: ", session_id(), "<br/>\n";
            echo "session age: ", time() - $_SESSION['creation_time'], " seconds<br/>\n";
            if (isset($inactivity)) echo "you were inactive for $inactivity seconds<br/>\n";
            echo "MAX_IDLE_MINUTES_BEFORE_LOGOUT = ", MAX_IDLE_MINUTES_BEFORE_LOGOUT, " minutes = ", MAX_IDLE_MINUTES_BEFORE_LOGOUT * 60, " seconds<br/>\n";
            echo "MAX_SESSION_ID_LIFETIME = ", MAX_SESSION_ID_LIFETIME, " minutes = ", MAX_SESSION_ID_LIFETIME * 60, " seconds<br/>\n";
            echo "</p></div>\n";
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

    show_footer($is_logged_in, $is_admin);

    if ($lock) release_lock($lock);
}
?>
