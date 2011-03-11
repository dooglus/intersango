<?php
require 'openid.php';
echo "        <div class='content_box'><div class='content_sideshadow'>";

echo '<h3>Private user info</h3>';
if (isset($_SESSION['uid'])) {
    echo '<p>You are logged in.</p>';
    $uid = $_SESSION['uid'];
    $oidlogin = $_SESSION['oidlogin'];
    echo '<p>User ID: '.$uid.'</p>';
    echo '<p>OpenID: '.$oidlogin.'</p>';
    require 'db.php';
    $query = "SELECT amount, type FROM purses WHERE uid='".$uid."';";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $amount = internal_to_numstr($row['amount']);
        $type = $row['type'];
        echo '<p>You have '.$amount.' '.$type.'</p>';
    }
}
else
    echo '<h3>Denied</h3><p>Go away.</p>';

echo '        </div></div>';
?>
