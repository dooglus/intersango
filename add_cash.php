<?php

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
        echo "<p>deposit should show up in their account in a minute or two</p>\n";
        echo "<p>make another deposit?</p>\n";
        $amount= $reference = '';
    } else {
        echo "<p>'$reference' isn't a valid reference code\n";
        echo "<p>try again?</p>\n";
    }
} else
    $amount = $reference = '';
?>
    <form action='' class='indent_form' method='post'>
        <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
        <input type='hidden' name='deposit_cash' value='true' />
        reference: <input type='text' name='reference' value='<?php echo $reference; ?>'/>
        amount: <input type='text' name='amount' value='<?php echo $amount; ?>' />
        <input type='submit' value='Deposit' />
    </form> 
</div>
