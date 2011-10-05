<?php
require_once "../config.php";
require_once ABSPATH . "/header.php";
require_once ABSPATH . "/footer.php";

session_start();

date_default_timezone_set(TIMEZONE);

get_login_status();

show_header('api', $is_logged_in, URLROOT);
?>
<div class='content_box'>
    <h3>API pages</h3>
    <ul>
        <li><a href="api/getDepth.php">getDepth.php</a><br/>
        <li><a href="api/getTrades.php">getTrades.php</a><br/>
        <li><a href="api/ticker.php">ticker.php</a><br/>
    </ul>
</div>
<?php show_footer($is_logged_in, $is_admin); ?>
