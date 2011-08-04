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
            echo "                    <div class='content_box'>\n";
            echo "                    <h3>Successful login!</h3>\n";
            echo "                    <p>Welcome back commander. Welcome back.</p>\n";

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
        }
        else {
            echo "bad 2nd auth?<br/>\n";
            // throw new Problem(":(", "Unable to login.");
        }
    }
    else {
        $openid = new LightOpenID;
        if (!$openid->mode) {
            if (isset($_GET['openid_identifier'])) {
                $openid->identity = htmlspecialchars($_GET['openid_identifier'], ENT_QUOTES);
                header('Location: '.$openid->authUrl());
            }
?>
<div class='content_box'>
<h3>Login</h3>
<p>Enter your OpenID login below:</p>
<p>
    <form action='' class='indent_form' method='get'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='text' name='openid_identifier' />
        <input type='hidden' name='page' value='login' />
        <input type='submit' value='Submit' />
    </form>
</p>
<p>If you do not have an OpenID login then we recommend <a href="https://www.myopenid.com/">MyOpenID</a>.</p>
<p>Alternatively you may sign in using <a href="?page=login&openid_identifier=https://www.google.com/accounts/o8/id&csrf_token=<?php echo $_SESSION['csrf_token']; ?>">Google</a> or <a href="?page=login&openid_identifier=me.yahoo.com&csrf_token=<?php echo $_SESSION['csrf_token']; ?>">Yahoo</a>.</p>
<?php
        }
        else if ($openid->mode == 'cancel') {
            throw new Problem(":(", "Login was cancelled.");
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
                $sig_request = Duo::signRequest(IKEY, SKEY, AKEY, $oidlogin); ?>
    <script src="Duo-Web-v1.bundled.min.js"></script>
    <script>
        Duo.init({'host': <?php echo "'" . HOST . "'"; ?>,
                  'post_action': '?page=login',
                  'sig_request': <?php echo "'" . $sig_request . "'"; ?> });
    </script>
    <iframe id="duo_iframe" width="500" height="800" frameborder="0" allowtransparency="true" style="background: transparent;"></iframe>
<?php
            } else {
                echo "                    <div class='content_box'>\n";
                echo "                        <h3>Successful login!</h3>\n";
                if (has_results($result))
                    echo "                        <p>Welcome back commander. Welcome back.</p>\n";
                else {
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
                    // generate random str for deposit reference
                    $query = "
                        INSERT INTO purses
                            (uid, amount, type)
                        VALUES
                            (LAST_INSERT_ID(), 0, 'AUD');
                    ";
                    do_query($query);
                    $query = "
                        INSERT INTO purses
                            (uid, amount, type)
                        VALUES
                            (LAST_INSERT_ID(), 0, 'BTC');
                    ";
                    do_query($query);
                    echo "                        <p>Nice to finally see you here, <i>new</i> user.</p>\n";
                    echo "                        <p>Now you may wish <a href='?page=deposit'>deposit</a> funds before continuing.</p>\n";
                }

                // store for later
                $_SESSION['oidlogin'] = $oidlogin;
                $_SESSION['uid'] = $uid;
            }
        } else {
            throw new Problem(":(", "Unable to login.  Please <a href='?page=login'>try again</a>.");
        }
    }
}
catch (ErrorException $e) {
    throw new Problem(":(", $e->getMessage());
} 
# close content box
?>
                    </div>
