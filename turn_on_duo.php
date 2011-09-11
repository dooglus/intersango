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
    <h3><?php echo _("Alright!"); ?></h3>
    <p><?php echo _("Two-factor authentification is now turned on for your account."); ?></p>
    <p><?php echo _("You have been logged out."); ?></p>
    <p><?php printf(_("When you %slog back in%s, you will be asked to set up details for the two-factor authenfication."),
                    '<a href="?page=login">',
                    '</a>'); ?></p>
</div>
<?php } else { ?>
<div class='content_box'>
    <h3><?php echo _("Two factor authentication"); ?></h3>

    <p><?php printf(_("Read about two factor authentication %shere%s.
       Then click below if you want to opt in to using it."),
                    '<a target="_blank" href="?page=help#two_factor">',
                    '</a>'); ?></p>

    <form action='' class='indent_form' method='post'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='enable_duo' value='true' />
        <input type='submit' value='<?php echo _("Opt in"); ?>' />
    </form>
</div>
<?php } ?>
