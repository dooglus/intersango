<?php
require 'openid.php';
echo "        <div class='content_box'>\n<div class='content_sideshadow'>";
try {
    $openid = new LightOpenID;
    if (!$openid->mode) {
        if (isset($_POST['openid_identifier'])) {
            $openid->identity = $_POST['openid_identifier'];
            header('Location: '.$openid->authUrl());
        }
?>
<h3>Login</h3>
<p>Enter your OpenID login below:</p>
<p>
    <form action='' method='post'>
        <input type='text' name='openid_identifier' />
        <input type='submit' value='Submit' />
    </form>
</p>
<p>If you do not have an OpenID login then we recommend <a href="https://www.myopenid.com/">MyOpenID</a>.
<?php
    }
    else if ($openid->mode == 'cancel') {
        echo '<h3>Login problem</h3><p>:(</p>';
    }
    else {
        if ($openid->validate()) {
            require 'db.php';

            echo '<h3>Successful login!</h3>';
            # protect against session hijacking now we've escalated privilege level
            session_regenerate_id(true);
            $oidlogin = escapestr($openid->identity);
            # fetch record from db
            $query = "SELECT * FROM users WHERE oidlogin='".$oidlogin."';";
            $result = do_query($query);

            if (has_results($result))
                echo '<p>Welcome back commander. Welcome back.</p>';
            else {
                echo '<p>Nice to finally see you here, <i>new</i> user.</p>';
                $insq = "INSERT INTO users(oidlogin) VALUES ('".$oidlogin."');";
                do_query($insq);
                # reperform query so we can store new uid
                $result = do_query($query);
            }
            $row = mysql_fetch_array($result);
            # store for later
            $_SESSION['oidlogin'] = $oidlogin;
            $_SESSION['uid'] = $row['uid'];
        }
        else {
            echo '<h3>Login problem</h3><p>:(</p>';
        }
    }
}
catch (ErrorException $e) {
    echo '<p>'.$e->getMessage().'</p>';
}
# close content box
echo '        </div></div>';
?>
