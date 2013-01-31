<div class='content_box'>
<h3>Welcome</h3>
<p>
This is the WBX claim site.
</p>
<p>
To stake your claim of part of the WBX funds, please take the following steps:
</p>
<?php
     $current1 = $current2 = $current3 = '';
     if (!$is_logged_in)
         $current1 = " class='current'";
     else if (!$is_verified)
         $current2 = " class='current'";
     else
         $current3 = " class='current'";
     echo "<ul>
<li$current1>1. Use the 'login' link to log in here using the same OpenID account as you used at WBX.
<li$current2>2. Use the 'identify' link to upload documents that verify your identity if your account is not already verified.
<li$current3>3. Use the 'claim' link to stake your claim.  You to provide a Bitcoin address and optionally additional notes.
"; ?>
</ul>
</div>

<div class='content_box'>
<h3>Status</h3>
<?php
     if (!$is_logged_in) {
         echo "<p>You are currently on step 1.</p>\n";
         echo "<p>Please click the 'login' link on the right to log in using your WBX credentials.</p>\n";
     } else if (!$is_verified) {
         echo "<p>You are currently on step 2.</p>\n";
         echo "<p>Please click the 'identify' link on the right to upload documents that verify your identity.</p>\n";
     } else {
         echo "<p>You are currently on step 3.</p>\n";
         echo "<p>Please click the 'claim' link on the right to specify your Bitcoin address and any additional notes you wish to make.</p>\n";
     }
?>

</div>
