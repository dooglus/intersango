<?php

require_once 'util.php';

if (isset($_POST['set_freeze'])) {
    if (isset($_POST['csrf_token'])) {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf", "csrf token mismatch!");
    } else
        throw new Error("csrf", "csrf token missing!");
}

echo "<div class='content_box'>\n";
echo "<h3>Freeze!</h3>\n";

if (isset($_POST['set_freeze'])) {
    $state = post('set_freeze');

    if ($state == 'freeze') {
        set_frozen(true);
        echo "<p>Exchange has been frozen. <a href=".">continue</a></p>\n";
    } else if ($state == 'unfreeze') {
        set_frozen(false);
        echo "<p>Exchange has been unfrozen. <a href=".">continue</a></p>\n";
    } else
        throw Error("Unknown state", "State $state should be 'freeze' or 'unfreeze'.");
} else {
    $is_frozen = is_frozen();
    if ($is_frozen) {
        echo "<p>The exchange is currently frozen.</p>\n";
        echo "<p>Click 'unfreeze' below to resume order matching and withdrawal processing.</p>\n";
    } else {
        echo "<p>The exchange isn't currently frozen.</p>\n";
        echo "<p>Click 'freeze' below to freeze order matching and withdrawal processing.</p>\n";
        echo "<p>Users will still be able to place and cancel orders,\n";
        echo "they just won't be matched until after you unfreeze the exchange.</p>\n";
    }
?>
    <form action='' class='indent_form' method='post'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='set_freeze'' value='<?php echo $is_frozen ? "unfreeze" : "freeze" ?>' />
        <input type='submit' value='<?php echo $is_frozen ? "Unfreeze" : "Freeze" ?>' />
    </form> 
<?php
}

echo "</div>\n";
?>
