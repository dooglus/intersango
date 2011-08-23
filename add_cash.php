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

    $result = do_query("SELECT deposref FROM users WHERE uid > 1 ORDER BY deposref");

    while ($row = mysql_fetch_assoc($result)) {
        $deposref = strtolower($row['deposref']);
        $scores[$deposref] = round((8 + similar_text($reference, $deposref) - levenshtein($reference, $deposref))*100/16);
    }

    arsort($scores);

    $first = true;
    foreach ($scores as $deposref => $score) {
        if ($score >= 50) {
            if ($first) {
                $first = false;
                echo "<p>Did you mean one of these?  Higher percentage = closer match.</p>\n";
                echo "<p>Click an entry to copy it to the form below, then click 'Deposit' again.</p>\n";
                echo "<table class='display_data'>\n";
            }
            echo "<tr",
                " class=\"me\"",
                " onmouseover=\"style.backgroundColor='#8ae3bf';\"",
                " onmouseout=\"style.backgroundColor='#7ad3af';\"",
                " onclick=\"ObjById('reference').value = '$deposref';\">";
            echo "<td>$deposref</td><td>$score%</td></tr>\n";
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
echo "<h3>Deposit cash</h3>\n";

if (!$is_admin) throw new Error("GTFO", "How did you get here?");

if (isset($_POST['deposit_cash'])) {

    $reference = post('reference');
    $amount = post('amount');
    $amount_internal = numstr_to_internal($amount);

    $query = "SELECT uid FROM users WHERE deposref='$reference'";
    $result = do_query($query);
    if (has_results($result)) {
        $row = get_row($result);
        $user = $row['uid'];
        // echo "<p>$reference is the code for user $user</p>\n";

        $query = "
            INSERT INTO requests (req_type, curr_type, uid,   amount )
            VALUES               ('DEPOS',  'AUD',     $user, $amount_internal)
        ";
        do_query($query);
        echo "<p><span style='font-weight: bold;'>added request to deposit $amount AUD to user $user's purse (reference $reference)</span></p>\n";
        echo "<p>deposit should show up in their account <string>in a minute or two</strong></p>\n";
        echo "<p>make another deposit?</p>\n";
        $amount= $reference = '';
    } else {
        echo "<p>'$reference' isn't a valid reference code\n";
        show_similar_codes($reference);
        echo "<p>try again?</p>\n";
    }
} else
    $amount = $reference = '';
?>
    <form action='' class='indent_form' method='post'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='deposit_cash' value='true' />

        <label for='reference'>Reference</label>
        <input id='reference' type='text' name='reference' value='<?php echo $reference; ?>'/>

        <label for='amount'>Amount</label>
        <input id='amount' type='text' name='amount' value='<?php echo $amount; ?>' />

        <input type='submit' value='Deposit' />
    </form> 
</div>
