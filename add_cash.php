<script type="text/javascript">
function ObjById(id) 
{ 
    if (document.getElementById) 
        var returnVar = document.getElementById(id); 
    else if (document.all) 
        var returnVar = document.all[id]; 
    else if (document.layers) 
        var returnVar = document.layers[id]; 
    return returnVar; 
}
</script>

<?php

function show_similar_codes($reference)
{
    $reference = strtolower($reference);

    $result = do_query("
            SELECT deposref, uid FROM users WHERE uid > 1
        UNION
            SELECT deposref, uid FROM old_deposrefs
        ORDER BY deposref
    ");

    while ($row = mysql_fetch_assoc($result)) {
        $deposref = strtolower($row['deposref']);
        $scores[$deposref] = round((9 + similar_text($reference, $deposref) - levenshtein($reference, $deposref))*100/18);
        $uid[$deposref] = $row['uid'];
    }

    arsort($scores);

    $first = true;
    foreach ($scores as $deposref => $score) {
        if ($score >= 50) {
            if ($first) {
                $first = false;
                echo "<p>" . _("Did you mean one of these?  Higher percentage = closer match.") . "</p>\n";
                echo "<p>" . _("Click an entry to copy it to the form below, then click 'Deposit' again.") . "</p>\n";
                echo "<table class='display_data'>\n";
                echo "<tr><th>Reference</th><th>Match</th><th>UID</th></tr>\n";
            }

            $formatted = format_deposref($deposref);

            echo "<tr",
                " class=\"me\"",
                " onmouseover=\"style.backgroundColor='#8ae3bf';\"",
                " onmouseout=\"style.backgroundColor='#7ad3af';\"",
                " onclick=\"ObjById('reference').value = '$deposref';\">";
            echo "<td>$formatted</td><td>$score%</td><td>{$uid[$deposref]}</td></tr>\n";
        }
    }

    if (!$first) echo "</table>\n";
}

if (isset($_POST['make_deposit'])) {
    if (isset($_POST['csrf_token'])) {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf", "csrf token mismatch!");
    } else
        throw new Error("csrf", "csrf token missing!");
}

echo "<div class='content_box'>\n";
echo "<h3>" . _("Deposit cash") . "</h3>\n";

if (!$is_admin) throw new Error("GTFO", "How did you get here?");

if (isset($_POST['deposit_cash'])) {

    $reference = post('reference');
    $user = post('user');
    $amount = post('amount');
    $amount_internal = numstr_to_internal($amount);

    if ($reference && $user)
        throw new Error("Error", "Only specify one of 'Reference' and 'User ID'");

    if ($reference) {
        $ref_without_spaces = str_replace(' ', '', $reference);
        $query = "
                SELECT uid FROM users WHERE deposref='$ref_without_spaces'
            UNION
                SELECT uid FROM old_deposrefs WHERE deposref='$ref_without_spaces'
        ";
        $result = do_query($query);
        if (has_results($result)) {
            $row = get_row($result);
            $user = $row['uid'];

            if (is_numeric($amount) && $amount != 0) {
                $query = "
                    INSERT INTO requests (req_type, curr_type, uid,   amount )
                    VALUES               ('DEPOS',  '" . CURRENCY . "',     $user, $amount_internal)
                ";
                do_query($query);
                printf("<p><span style='font-weight: bold;'>" . _("added request to deposit %s to user %s's purse (reference %s)") . "</span></p>\n",
                       ($amount . " " . CURRENCY), $user, $reference);
                echo "<p>" . _("deposit should show up in their account") . " <string>" . _("in a minute or two") . "</strong></p>\n";
                echo "<p>" . _("make another deposit?") . "</p>\n";
            } else
                echo "<p>$reference is the code for user $user</p>\n";
            $amount = $reference = $user = '';
        } else {
            printf("<p>" . _("'%s' isn't a valid reference code") . "</p>\n",
                   $reference);
            show_similar_codes($reference);
            echo "<p>" . _("try again?") . "</p>\n";
        }
    } else {
        $query = "SELECT deposref FROM users WHERE uid='$user'";
        $result = do_query($query);
        if (has_results($result)) {
            $row = get_row($result);
            $reference = $row['deposref'];

            if (is_numeric($amount) && $amount != 0) {
                $query = "
                    INSERT INTO requests (req_type, curr_type, uid,   amount )
                    VALUES               ('DEPOS',  '" . CURRENCY . "',     $user, $amount_internal)
                ";
                do_query($query);
                printf("<p><span style='font-weight: bold;'>" . _("added request to deposit %s to user %s's purse (reference %s)") . "</span></p>\n",
                       ($amount . " " . CURRENCY), $user, $reference);
                echo "<p>" . _("deposit should show up in their account") . " <string>" . _("in a minute or two") . "</strong></p>\n";
                echo "<p>" . _("make another deposit?") . "</p>\n";
            } else
                echo "<p>$reference is the code for user $user</p>\n";
            $amount = $reference = $user = '';
        } else {
            printf("<p>" . _("'%s' isn't a valid userid") . "</p>\n",
                   $user);
            echo "<p>" . _("try again?") . "</p>\n";
        }
    }
} else
    $amount = $reference = $user = '';
    echo "    <p>" . _("Specify either 'Reference' or 'User ID', but not both.") . "</p>\n";
?>
    <form action='' class='indent_form' method='post'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='deposit_cash' value='true' />

        <label for='reference'><?php echo _("Reference"); ?></label>
        <input id='reference' type='text' name='reference' value='<?php echo $reference; ?>'/>

        <label for='user'><?php echo _("User ID"); ?></label>
        <input id='user' type='text' name='user' value='<?php echo $user; ?>'/>

        <label for='amount'><?php echo _("Amount"); ?></label>
        <input id='amount' type='text' name='amount' value='<?php echo $amount; ?>' />

        <input type='submit' value='Deposit' />
    </form> 
</div>
