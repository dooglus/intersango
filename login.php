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
            # protect against session hijacking now we've escalated privilege level
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

            addlog(sprintf("  duo login by UID %s (openid %s)", $uid, $oidlogin));
            show_header('login', $uid);
            echo "                    <div class='content_box'>\n";
            echo "                    <h3>" . _("Successful login!") . "</h3>\n";
            echo "                    <p>" . _("Welcome back commander. Welcome back.") . "</p>\n";
        }
        else {
            show_header('login', 0);
            echo "bad 2nd auth?<br/>\n";
            // throw new Problem(":(", "Unable to login.");
        }
    }
    else {
        $openid = new LightOpenID;
        if (!$openid->mode) {
            if (isset($_GET['openid_identifier'])) {
                $openid->identity = htmlspecialchars($_GET['openid_identifier'], ENT_QUOTES);
                addlog(sprintf("  attempt auth for openid %s", $openid->identity));
                header('Location: '.$openid->authUrl());
            } else
                addlog("  showing login form");

            show_header('login', 0);
?>
<div class='content_box'>
<h3><?php echo _("Login"); ?></h3>
<p><?php echo _("Enter your OpenID login below:"); ?></p>
<p>
    <form action='' class='indent_form' method='get'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='text' name='openid_identifier' />
        <input type='hidden' name='page' value='login' />
        <input type='submit' value='<?php echo _("Submit"); ?>' />
    </form>
</p>
<p><?php printf(_("If you do not have an OpenID login then we recommend %s."), "<a href=\"https://www.myopenid.com/\">MyOpenID</a>"); ?></p>
<p><?php printf(_("Alternatively you may sign in using %s or %s."),
                "<a href=\"?page=login&openid_identifier=https://www.google.com/accounts/o8/id&csrf_token=" . $_SESSION['csrf_token'] . "\">Google</a>",
                "<a href=\"?page=login&openid_identifier=me.yahoo.com&csrf_token=" . $_SESSION['csrf_token'] . "\">Yahoo</a>"); ?></p>
<?php
        }
        else if ($openid->mode == 'cancel') {
            show_header('login', 0);
            throw new Problem(":(", _("Login was cancelled."));
        }
        else if ($openid->validate()) {
            # protect against session hijacking now we've escalated privilege level
            session_regenerate_id(true);

            $oidlogin = escapestr($openid->identity);
            $use_duo = 0;

            # is this OpenID known to us?
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
                addlog(sprintf("  duo login for UID %s (openid %s)", $uid, $oidlogin));
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
                    addlog(sprintf("  regular login by UID %s (openid %s)", $uid, $oidlogin));
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
                            SUBSTR(MD5(RAND()), 8)
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

                    addlog(sprintf("  new user UID %s (openid %s)", $uid, $oidlogin));
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
        } else {
            show_header('login', 0);
            throw new Problem(":(", sprintf(_("Unable to login.  Please %stry again%s."),
                                            '<a href="?page=login">',
                                            '</a>'));
        }
    }
}
catch (ErrorException $e) {
    throw new Problem(":(", $e->getMessage());
} 
# close content box
?>
                    </div>
