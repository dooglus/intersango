<?php
require 'openid.php';
require 'util.php';

echo "        <div class='content_box'>";

echo '<h3>Private user info</h3>';
if (isset($_SESSION['uid'])) {
    echo '<p>You are logged in.</p>';
    $uid = $_SESSION['uid'];
    $oidlogin = $_SESSION['oidlogin'];
    echo '<p>User ID: '.$uid.'</p>';
    echo '<p>OpenID: '.$oidlogin.'</p>';
    show_balances();
}
else
    echo '<h3>Denied</h3><p>Go away.</p>';

echo '        </div>';
?>
