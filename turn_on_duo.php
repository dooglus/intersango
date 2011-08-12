<?php
require_once 'util.php';

if (isset($_POST['enable_duo'])) {
    if (isset($_POST['csrf_token'])) {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf", "csrf token mismatch!");
    } else
        throw new Error("csrf", "csrf token missing!");
}

if (isset($_POST['enable_duo'])) {
    $uid = user_id();

    do_query("update users set use_duo=1 where uid = $uid");
    session_destroy();
?>
<div class='content_box'>
    <h3>Alright!</h3>
    <p>Two-factor authentification is now turned on for your account.</p>
    <p>You have been logged out.</p>
    <p>When you <a href="?page=login">log back in</a>, you will be asked to set up details for the two-factor authenfication.</p>
</div>
<?php } else { ?>
<div class='content_box'>
    <h3>Two factor authentication</h3>

    <p>Read about two factor authentication <a target="_blank" href="?page=help#two_factor">here</a>.
       Then click below if you want to opt in to using it.</p>

    <form action='' class='indent_form' method='post'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='enable_duo' value='true' />
        <input type='submit' value='Opt in' />
    </form>
</div>
<?php } ?>
