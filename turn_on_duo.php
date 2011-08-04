<?php
require_once 'util.php';

$uid = user_id();

do_query("update users set use_duo=1 where uid = $uid");
session_destroy();

?>
<div class='content_box'>
<h3>Alright!</h3>
<p>Two-factor authentification is now turned on for your account.</p>
<p>You have been logged out.</p>
<p>When you <a href="?page=login">log back in</a>, you'll be asked to set up details for the two-factor authenfication.</p>
</div>
