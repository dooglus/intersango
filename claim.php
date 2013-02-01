<?php

if (isset($_POST['address']) or isset($_POST['note']))
    if (isset($_POST['csrf_token'])) {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf","csrf token mismatch!");
    }
    else
        throw new Error("csrf","csrf token missing!");

function claim_insert_bitcoin_address($uid)
{
    $address = post('address');

    try {
        $validaddy = bitcoin_validate_address($address);
    } catch (Exception $e) {
        if ($e->getMessage() != 'Unable to connect.')
            throw $e;
        throw new Problem(_("Sorry..."),
                          _("We are currently experiencing trouble connecting to the Bitcoin network and so cannot verify that you entered a valid Bitcoin address.") .
                          "</p><p>" .
                          _("Please try again in a few minutes."));
    }

    if (!$validaddy['isvalid'])
        throw new Problem(_('Bitcoin says no'), _('That address you supplied was invalid.'));

    $query = "INSERT INTO bitcoin_addresses (uid, addy) VALUES ($uid, '$address')";
    do_query($query);
}

function claim_insert_note($uid)
{
    $note = post('note', '\n?!\'"$[]()');
    $query = "INSERT INTO notes (uid, note) VALUES ($uid, '$note')";
    do_query($query);
}

function show_additional_notes($uid)
{
    $query = "SELECT note, timest FROM notes WHERE uid = $uid ORDER BY timest";
    $result = do_query($query);
    $notes = 0;
    while ($row = mysql_fetch_array($result)) {
        $notes++;
        print "<p>" . $row['timest'] . ":</p>\n";
        print "<div class='note'><p><pre>" . $row['note'] . "</pre></p></div>\n";
    }

    if ($notes) {
        print "<p>Anything else to add?</p>\n";
    } else {
        print "<p>Are your balances correct?  If not, please give details below.</p>\n";
    }
}

if (isset($_POST['address']))
    claim_insert_bitcoin_address($is_logged_in);
else if (isset($_POST['note']))
    claim_insert_note($is_logged_in);

print "<div class='content_box'>
<h3>Stake your claim</h3>
<p>
";

/* bitcoin address */

$query = "SELECT addy FROM bitcoin_addresses WHERE uid = $is_logged_in";
$result = do_query($query);
if (!has_results($result)) {
    print "<p>Type the Bitcoin address that you want your refund sent to, then save it:</p>\n";
    print "<form class='indent_form' method='post'>
<input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}' />
<input type='text' name='address'>
<input type='submit' value='Save Bitcoin Address'>
</form>
</div>\n";
    return;
}

$row = get_row($result);
print "<p>Your bitcoin address: " . $row['addy'] . "</p>\n";
print "</div>\n";

/* additional notes */

print "<div class='content_box'>\n";
print "<h3>Additional Notes</h3>\n";

show_additional_notes($is_logged_in);

    print "<form class='indent_form' method='post'>
<input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}' />
<textarea rows='12' cols='80' name='note'></textarea><br/>
<input type='submit' value='Save Note'>
</form>
</div>
";

print "</div>\n";

?>
