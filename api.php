<?php

if (isset($_POST['add_key']) ||
    isset($_POST['update_permissions']) ||
    isset($_POST['delete_key']))
{
    if (isset($_POST['csrf_token']))
    {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf","csrf token mismatch!");
    }
    else
        throw new Error("csrf","csrf token missing!");
}

function show_api_keys()
{
    global $is_logged_in, $is_admin;

    echo "<div class='content_box'>\n";
    echo "<h3>" . _("API Keys") . "</h3>\n";

    $result = do_query("
        SELECT
            name, api_key, secret,
            can_read,
            can_trade,
            can_withdraw,
            can_deposit
        FROM
            api_keys
        WHERE
            uid = $is_logged_in
        ORDER BY
            name
    ");

    $first = true;
    $count = 0;
    while ($row = mysql_fetch_array($result)) {
        if ($first) $first = false;
        $count++;

        $name = $row['name'];
        $key = $row['api_key'];
        $secret = $row['secret'];
        $can_read = $row['can_read'];
        $can_trade = $row['can_trade'];
        $can_withdraw = $row['can_withdraw'];
        $can_deposit = $row['can_deposit'];

        echo "<form action='' class='indent_form' method='post'>\n";
        echo "<table class='display_data'>\n";

        echo "<tr><th class='right'>" . _("Name") . "</th><th>$name</th></tr>\n";
        echo "<tr><th class='right'>" . _("Key") . "</th><td class='small_mono'>$key</td></tr>\n";
        echo "<tr><th class='right'>" . _("Secret") . "</th><td class='small_mono'>$secret</td></tr>\n";
        echo "<tr><th class='right'>" . _("Permissions") . "</th><td>";
?>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='update_permissions' value='true' />
        <input type='hidden' name='name' value='<?php echo $name; ?>' />
        <input type='checkbox' id='read<?php echo $count; ?>' name='read' value='1'<?php if ($can_read) echo " checked='checked'" ?> />
        <label style="margin: 0px; display: inline;" for='read<?php echo $count; ?>'>read</label>
        <input type='checkbox' id='trade<?php echo $count; ?>' name='trade' value='1'<?php if ($can_trade) echo " checked='checked'" ?> />
        <label style="margin: 0px; display: inline;" for='trade<?php echo $count; ?>'>trade</label>
        <input type='checkbox' id='withdraw<?php echo $count; ?>' name='withdraw' value='1'<?php if ($can_withdraw) echo " checked='checked'" ?> />
        <label style="margin: 0px; display: inline;" for='withdraw<?php echo $count; ?>'>withdraw</label>
        <input type='checkbox' id='deposit<?php echo $count; ?>' name='deposit' value='1'<?php if ($can_deposit) echo " checked='checked'" ?> />
        <label style="margin: 0px; display: inline;" for='deposit<?php echo $count; ?>'>deposit</label>
        </td></tr>
        <tr><td></td><td>
        <input type='submit' value='<?php echo _("Update Permissions"); ?>' />
        </form> 
        <form action='' method='post' style='display: inline'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='delete_key' value='true' />
        <input type='hidden' name='name' value='<?php echo $name; ?>' />
        <input type='submit' value='<?php echo _("Delete Key"); ?>' />
        </form> 
        </td></tr>
        </table>
<?php
    }

    if ($first)
        echo "<p>You currently have no API keys.</p>\n";

?>
        </div>
        <div class='content_box'>
        <h3>Create New API Key</h3>
        <p>
            <form action='' class='indent_form' method='post'>
                <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
                <input type='hidden' name='add_key' value='true' />

                <label for='name'><?php echo _("Name"); ?></label>
                <input id='name' type='text' name='name' value='my key' />

                <p><?php echo _("Permissions"); ?></p>
                <input id='read' type='checkbox' name='read' value='1' checked='checked' />
                <label style="margin: 0px; display: inline;" for='read'>read</label>
                <input id='trade' type='checkbox' name='trade' value='1' />
                <label style="margin: 0px; display: inline;" for='trade'>trade</label>
                <input id='withdraw' type='checkbox' name='withdraw' value='1' />
                <label style="margin: 0px; display: inline;" for='withdraw'>withdraw</label>
                <input id='deposit' type='checkbox' name='deposit' value='1' />
                <label style="margin: 0px; display: inline;" for='deposit'>deposit</label>

                <br/><br/><input type='submit' value='Add New API Key' />
            </form>
        </p>
<?php
    echo "</div>\n";
}

$read     = isset($_POST['read']) ? 1 : 0;
$trade    = isset($_POST['trade']) ? 1 : 0;
$withdraw = isset($_POST['withdraw']) ? 1 : 0;
$deposit  = isset($_POST['deposit']) ? 1 : 0;

if (isset($_POST['add_key'])) {
    $name     = post('name');
    $api_key  = random_string(8,5);
    $secret   = random_string(8,8);

    // don't generate keys too quickly
    usleep(rand(1e6, 2e6));

    $result = mysql_query("INSERT INTO api_keys (uid, name,    api_key,    secret, can_read, can_trade, can_withdraw, can_deposit)
                           VALUES ('$is_logged_in', '$name', '$api_key', '$secret',  '$read',  '$trade',  '$withdraw',  '$deposit')");
    if (!$result)
        throw new Error("Error creating key", "Do you already have an API key with that name?");
} else if (isset($_POST['update_permissions'])) {
    $name  = post('name');
    $query = "
        UPDATE
            api_keys
        SET
            can_read = $read, can_trade = $trade, can_withdraw = $withdraw, can_deposit = $deposit
        WHERE
            uid = '$is_logged_in'
        AND
            name = '$name'
    ";
    do_query($query);
} else if (isset($_POST['delete_key'])) {
    $name  = post('name');
    $query = "DELETE FROM api_keys wheRE uid = '$is_logged_in' AND name = '$name'";
    do_query($query);
}

show_api_keys();

?>
