<?php
require 'openid.php';
echo "        <div class='content_box'>";

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
        echo '<h3>'.$amount.' '.$type.'</h3>';
?>
<p>
<form action='?page=place_order' method='post'>
    Amount: <input type='text' name='amount' /><br />
    Want amount: <input type='text' name='want_amount' /><br />
    Want type: <input type='text' name='want_type' /><br />
    <input type='hidden' name='type' value='<?php echo $type;?>' />
    <input type='submit' value='Submit' />
</form>
</p>
<?php   
    }
}
else
    echo '<h3>Denied</h3><p>Go away.</p>';

echo '        </div>';
?>
