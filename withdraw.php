<?php
require 'util.php';
if (isset($_POST['amount']) && isset($_POST['curr_type'])) {
    $amount = $_POST['amount'];
    $curr_type = $_POST['curr_type'];
    $amount = numstr_to_internal($amount);
    ?>
    <div class='content_box'>
    <h3>Withdraw <?php echo $curr_type; ?></h3>
<?php
    echo "<p>Your request to withdraw $amount $curr_type has been submitted.</p>\n";
    echo "</div>\n";
    # echo blaa | mutt -s subject foo@bar.org
}
else {
?>
    <div class='content_box'>
    <h3>Withdraw GBP</h3>
    <p>Enter an amount below to submit a withdrawal request.</p>
    <p>
        <form action='' method='post'>
            <input type='text' name='amount' />
            <input type='hidden' name='curr_type' value='GBP' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>

    <div class='content_box'>
    <h3>Withdraw BTC</h3>
    <p>Enter an amount below to withdraw.</p>
    <p>
        <form action='' method='post'>
            <input type='text' name='amount' />
            <input type='hidden' name='curr_type' value='BTC' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>
<?php
}
?>

