<?php
require_once 'openid.php';
require_once "duo_config.php";
require_once "duo_web.php";
require_once 'db.php';

if(isset($_GET['openid_identifier']))
{
    if(isset($_GET['csrf_token']))
    {
        if($_SESSION['csrf_token'] != $_GET['csrf_token'])
        {
            throw new Error("csrf","csrf token mismatch!");
        }
    }
    else
    {
        throw new Error("csrf","csrf token missing");
    }
}

try {
    /* STEP 3: Once secondary auth has completed you may log in the user */
    if(isset($_POST['sig_response'])) {
        //verify sig response and log in user
        //make sure that verifyResponse does not return NULL
        //if it is NOT NULL then it will return a username you can then set any cookies/session data for that username and complete the login process
        $oidlogin = Duo::verifyResponse(IKEY, SKEY, AKEY, $_POST['sig_response']);
        if($oidlogin != NULL) {
            // protect against session hijacking now we've escalated privilege level
            session_regenerate_id(true);

            $query = "
                SELECT uid
                FROM users
                WHERE oidlogin='$oidlogin'
                LIMIT 1;
            ";
            $result = do_query($query);
            $row = get_row($result);
            $uid = (string)$row['uid'];

            // store for later
            $_SESSION['oidlogin'] = $oidlogin;
            $_SESSION['uid'] = $uid;

            addlog(LOG_LOGIN, sprintf("  duo login by UID %s (openid %s)", $uid, $oidlogin));
            show_header('login', $uid);
            echo "                    <div class='content_box'>\n";
            echo "                    <h3>" . _("Successful login!") . "</h3>\n";
            echo "                    <p>" . _("Welcome back commander. Welcome back.") . "</p>\n";
        } else {
            show_header('login', 0);
            echo "bad 2nd auth?<br/>\n";
            // throw new Problem(_("Login Error"), "Unable to login.");
        }
    } else {
        $openid = new LightOpenID;
        if (isset($next_page))
            $openid->returnUrl = SITE_URL . "?page=login&next_page=" . preg_replace('/&/', '%26', $next_page);
        if (!$openid->mode) {
            if (isset($_GET['openid_identifier'])) {
                $openid->identity = htmlspecialchars($_GET['openid_identifier'], ENT_QUOTES);
                addlog(LOG_LOGIN, sprintf("  attempt auth for openid %s", $openid->identity));

                if (isset($_GET['remember']))
                    setcookie('openid', $openid->identity, time() + 60*60*24*365);
                else
                    setcookie('openid', FALSE, time() - 60*60*24*365);

                if (isset($_GET['autologin']))
                    setcookie('autologin', $openid->identity, time() + 60*60*24*365);
                else
                    setcookie('autologin', FALSE, time() - 60*60*24*365);

                header('Location: '.$openid->authUrl());
            } else if (isset($_COOKIE['openid']) && isset($_COOKIE['autologin'])) {
                $openid->identity = $_COOKIE['openid'];
                addlog(LOG_LOGIN, sprintf("  autologin: attempt auth for openid %s", $openid->identity));
                header('Location: '.$openid->authUrl());
            } else
                addlog(LOG_LOGIN, "  showing login form");

            show_header('login', 0);

            $cookie = isset($_COOKIE['openid']) ? $_COOKIE['openid'] : FALSE;
            $autologin = isset($_COOKIE['autologin']);

            echo "<div class='content_box'>\n";
            echo "<h3>" . _("Login") . "</h3>\n";
            echo "<p>" . _("Enter your OpenID login below:") . "</p>\n";
            echo "<p>\n";
            echo "    <form action='' class='indent_form' method='get'>\n";
            echo "        <input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}' />\n";
            echo "        <input type='text' name='openid_identifier'" . ($cookie ? " value='$cookie'" : "") . " />\n";
            echo "        <input type='checkbox' id='remember' name='remember' value='1'" . ($cookie ? " checked='checked'" : "") . " />\n";
            echo "        <label style='margin: 0px; display: inline;' for='remember'>remember OpenID identifier on this computer</label><br/>\n";
            echo "        <input type='checkbox' id='autologin' name='autologin' value='1'" . ($autologin ? " checked='checked'" : "") . " />\n";
            echo "        <label style='margin: 0px; display: inline;' for='autologin'>automatically log in</label><br/>\n";
            echo "        <input type='hidden' name='page' value='login' /><br/>\n";
            if (isset($next_page)) {
                echo "        <input type='hidden' name='next_page' value='$next_page' /><br/>\n";
                echo "        <input type='hidden' name='oid' value='{$_SESSION['oidlogin']}' /><br/>\n";
            }
            echo "        <input type='submit' value='" . _("Submit") . "' />\n";
            echo "    </form>\n";
            echo "</p>\n";
            echo "<p>" . sprintf(_("If you do not have an OpenID login then we recommend %s."),
                                 "<a href=\"https://www.myopenid.com/\">MyOpenID</a>") . "</p>\n";
            echo "<p>" . sprintf(_("Alternatively you may sign in using %s or %s."),
                                 "<a href=\"?page=login&openid_identifier=https://www.google.com/accounts/o8/id&csrf_token=" .
                                 $_SESSION['csrf_token'] . "\">Google</a>",
                                 "<a href=\"?page=login&openid_identifier=me.yahoo.com&csrf_token=" .
                                 $_SESSION['csrf_token'] . "\">Yahoo</a>") . "</p>\n";
        } else if ($openid->mode == 'cancel')
            throw new Problem(_("Login Error"), _("Login was cancelled."));
        else if ($openid->validate()) {
            // protect against session hijacking now we've escalated privilege level
            session_regenerate_id(true);

            $oidlogin = escapestr($openid->identity);
            $use_duo = 0;

            // is this OpenID known to us?
            $query = "
                SELECT uid, use_duo
                FROM users
                WHERE oidlogin='$oidlogin'
                LIMIT 1;
            ";
            $result = do_query($query);

            if (has_results($result)) {
                $row = get_row($result);
                $use_duo = $row['use_duo'];
                $uid = (string)$row['uid'];
            }

            if ($use_duo) {
                addlog(LOG_LOGIN, sprintf("  duo login for UID %s (openid %s)", $uid, $oidlogin));
                show_header('login', 0);
                $sig_request = Duo::signRequest(IKEY, SKEY, AKEY, $oidlogin); ?>
    <script src="js/Duo-Web-v1.bundled.min.js"></script>
    <script>
        Duo.init({'host': <?php echo "'" . HOST . "'"; ?>,
                  'post_action': '?page=login',
                  'sig_request': <?php echo "'" . $sig_request . "'"; ?> });
    </script>
    <iframe id="duo_iframe" width="500" height="800" frameborder="0" allowtransparency="true" style="background: transparent;"></iframe>
<?php
            } else {
                if (has_results($result)) {
                    addlog(LOG_LOGIN, sprintf("  regular login by UID %s (openid %s)", $uid, $oidlogin));
                    if (isset($_GET['next_page'])) {
                        if (!isset($_GET['oid']) ||
                            !isset($_GET['openid_identifier']) ||
                            $_GET['oid'] == $_GET['openid_identifier']) {
                            $_SESSION['last_activity'] = time();
                            $_SESSION['oidlogin'] = $oidlogin;
                            $_SESSION['uid'] = $uid;
                            header('Location: ' . $_GET['next_page']);
                        }
                    }
                    show_header('login', $uid);
                    echo "                    <div class='content_box'>\n";
                    echo "                        <h3>" . _("Successful login!") . "</h3>\n";
                    echo "                        <p>" . _("Welcome back commander. Welcome back.") . "</p>\n";
                } else {
                    // generate random str for deposit reference
                    $query = "
                        INSERT INTO users (
                            oidlogin,
                            deposref
                        ) VALUES (
                            '$oidlogin',
                            CONCAT(FLOOR(RAND() * 900 + 100),
                                   LPAD(FLOOR(RAND() * 1000),3,'0'),
                                   LPAD(FLOOR(RAND() * 1000),3,'0'))
                        );
                    ";
                    do_query($query);
                    $uid = (string)mysql_insert_id();

                    $free_fiat = numstr_to_internal(FREE_FIAT_ON_SIGNUP);
                    $free_btc = numstr_to_internal(FREE_BTC_ON_SIGNUP);

                    $query = "
                        INSERT INTO purses
                            (uid, amount, type)
                        VALUES
                            (LAST_INSERT_ID(), $free_fiat, '" . CURRENCY . "');
                    ";
                    do_query($query);
                    $query = "
                        INSERT INTO purses
                            (uid, amount, type)
                        VALUES
                            (LAST_INSERT_ID(), $free_btc, 'BTC');
                    ";
                    do_query($query);

                    addlog(LOG_LOGIN, sprintf("  new user UID %s (openid %s)", $uid, $oidlogin));
                    show_header('login', $uid);

                    echo "                    <div class='content_box'>\n";
                    echo "                        <h3>" . _("Successful login!") . "</h3>\n";
                    echo "                        <p>" . _("Nice to finally see you here, <i>new</i> user.") . "</p>\n";
                    if (gmp_cmp($free_fiat, 0) > 0 or gmp_cmp($free_btc, 0))
                        echo "                        <p>" .
                            sprintf("We've given you %s and %s to test the exchange with.",
                                    internal_to_numstr($free_btc) . " BTC",
                                    internal_to_numstr($free_fiat) . " " . CURRENCY) .
                            "</p>\n";
                    echo "                        <p>" .
                        sprintf("Now you may wish to %sdeposit%s funds before continuing.",
                                '<a href="?page=deposit">',
                                '</a>') .
                        "</p>\n";
                }

                // store for later
                $_SESSION['oidlogin'] = $oidlogin;
                $_SESSION['uid'] = $uid;
            }
        } else
            throw new Problem(_("Login Error"), sprintf(_("Unable to login.  Please %stry again%s."),
                                                        '<a href="?page=login">',
                                                        '</a>'));
    }
}
catch (ErrorException $e) {
    throw new Problem(_("Login Error"), $e->getMessage());
} 
// close content box
?>
                    </div>
