<?php
# security protection
defined('_we_are_one') || die('Direct access not allowed.');

function switcher($page, $is_logged_in, $is_admin)
{
    try {
        // delay showing the header when logging in until we know whether the login worked or not
        if ($page != 'login')
            show_header($page, $is_logged_in);

        $lock = false;
        if ($is_logged_in) $lock = get_lock();

        switch($page) {
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

            case 'login':
                if (!$is_logged_in) {
                    include("login.php");

                    // we just tried to log in, so check whether or not it worked before showing the footer
                    list($is_logged_in, $is_admin) = array(is_logged_in(), is_admin());
                }
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

            case 'bank':
            case 'freeze':
                if ($is_admin)
                    include("$page.php");
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
            echo "max_idle_minutes_before_logout() = ", max_idle_minutes_before_logout(), " minutes = ", max_idle_minutes_before_logout() * 60, " seconds<br/>\n";
            echo "max_session_id_lifetime() = ", max_session_id_lifetime(), " minutes = ", max_session_id_lifetime() * 60, " seconds<br/>\n";
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
