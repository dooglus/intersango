<?php
require_once 'util.php';
require_once 'errors.php';

if(isset($_POST['cancel_request']))
{
    if(isset($_POST['csrf_token']))
    {
        if($_SESSION['csrf_token'] != $_POST['csrf_token'])
        {
            throw new Error("csrf","csrf token mismatch!");
        }
    }
    else
    {
        throw new Error("csrf","csrf token missing!");
    }
}

function display_request_info_fiat($reqid)
{
    $query = "
        SELECT name, bank, acc_num, sort_code
        FROM uk_requests
        WHERE reqid='$reqid'
    ";
    $result = do_query($query);
    $row = mysql_fetch_assoc($result);
    if (!$row)
        return;
    echo "<p>Name: {$row['name']}</p>\n";
    echo "<p>Bank: {$row['bank']}</p>\n";
    echo "<p>Account number: {$row['acc_num']}</p>\n";
    echo "<p>Sort code: {$row['sort_code']}</p>\n";
}

function display_request_info_btc($reqid)
{
    $query = "
        SELECT addy
        FROM bitcoin_requests
        WHERE reqid='$reqid'
    ";
    $result = do_query($query);
    $row = mysql_fetch_assoc($result);
    if ($row) {
        echo "<p>" . _("Bitcoin address") . ": {$row['addy']}</p>\n";
        return;
    }
}

function display_request_info_voucher($reqid)
{
    $query = "
        SELECT prefix
        FROM voucher_requests
        WHERE reqid='$reqid' OR redeem_reqid='$reqid'
    ";
    $result = do_query($query);
    $row = mysql_fetch_assoc($result);
    if ($row) {
        echo "<p>" . _("Voucher") . ": {$row['prefix']}-...</p>\n";
        return;
    }
}

function display_request_info_intnl($reqid)
{
    $query = "
        SELECT iban, swift
        FROM international_requests
        WHERE reqid='$reqid'
    ";
    $result = do_query($query);
    $row = mysql_fetch_assoc($result);
    if (!$row)
        return;
    echo "<p>" . _("IBAN") . ": {$row['iban']}</p>\n";
    echo "<p>" . _("BIC/SWIFT") . ": {$row['swift']}</p>\n";
}

function get_request_uid($reqid)
{
    $result = do_query("SELECT uid FROM requests WHERE reqid='$reqid'");
    $row = mysql_fetch_assoc($result);
    return $row['uid'];
}

$reqid = get('reqid');
$uid = user_id();

if (isset($_POST['cancel_request'])) {
    // cancel an order
    if ($is_admin) {
        $uid_check = "";
        $request_uid = get_request_uid($reqid);
        get_lock_without_waiting($request_uid);
    } else
        $uid_check = "AND requests.uid='$uid'";

    $query = "
        UPDATE
            requests
        JOIN
            purses
        ON
            purses.uid=requests.uid
            AND purses.type=requests.curr_type
        SET
            requests.status='CANCEL',
            purses.amount=purses.amount+requests.amount
        WHERE
            reqid='$reqid'
            $uid_check
            AND status='VERIFY'
            AND req_type='WITHDR'
    ";
    do_query($query);

    if ($is_admin)
        release_lock($request_uid);

    ?><div class='content_box'>
        <h3><?php echo "Cancelled!"; ?></h3>
        <p><?php printf(_("Request %s is no more."), $reqid); ?></p>
    </div><?php
} else if (isset($_POST['finish_request'])) {
    // mark an order's status as 'FINAL'
    if (!$is_admin) throw new Problem("Nope", "You don't have permission to do that");

    $request_uid = get_request_uid($reqid);
    get_lock_without_waiting($request_uid);

    $result = do_query("SELECT reqid FROM requests WHERE reqid='$reqid' AND status='VERIFY'");
    if (has_results($result)) {
        $query = "
            UPDATE
                requests
            SET
                requests.status='FINAL'
            WHERE
                reqid='$reqid'
                AND status='VERIFY'
                AND req_type='WITHDR'
                AND curr_type = '" . CURRENCY . "'
        ";
        do_query($query);
        echo "    <div class='content_box'>\n";
        echo "        <h3>" . _("Finished!") . "</h3>\n";
        echo "        <p>" . sprintf(_("Request %s has been set to %s status."),
                                     $reqid,
                                     translate_request_code("FINAL")) . "</p>\n";
    } else {
        echo "    <div class='content_box'>\n";
        echo "        <h3>" . _("Warning!") . "</h3>\n";
        echo "        <p>" . sprintf(_("Request %s was cancelled before we could mark it as finished."),
                                     $reqid) . "</p>\n";
    }
    release_lock($request_uid);

} else {
    if ($is_admin)
        $uid_check = "";
    else
        $uid_check = "AND uid='$uid'";

    $query = "
        SELECT
            req_type,
            amount,
            curr_type,
            " . sql_format_date("timest") . " AS timest,
            status
        FROM requests
        WHERE reqid='$reqid' $uid_check
    ";
    $result = do_query($query);
    if (!has_results($result))
        throw new Problem('No request here', "Don't have viewing permissions.");
    $row = get_row($result);
    $req_type = $row['req_type'];
    $amount = internal_to_numstr($row['amount']);
    $curr_type = $row['curr_type'];
    $timest = $row['timest'];
    $status = $row['status'];
    ?> <div class='content_box'>
        <h3><?php echo _("Order info"); ?></h3>
        <p>
        <?php printf(_("Request %s"), $reqid); ?>
        </p>
        <?php
        if ($req_type == 'WITHDR')
            echo "<p>" . sprintf(_("Withdrawing %s."), "$amount $curr_type") . "</p>\n";
        else
            echo "<p>" . sprintf(_("Depositing %s."), "$amount $curr_type") . "</p>\n";
        ?>
        <p>
        <?php
        // only one of these will return a result
        display_request_info_fiat($reqid);
        display_request_info_btc($reqid);
        display_request_info_voucher($reqid);
        display_request_info_intnl($reqid);
        ?>
        </p>
        <p>
        <?php printf(_("Made %s"), $timest); ?>
        </p>
        <p>
        <?php echo translate_request_code($status); ?>
        </p>
        <?php if ($status == 'VERIFY' && $req_type == 'WITHDR') { ?>
            <p>
            <form action='' class='indent_form' method='post'>
                <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
                <input type='hidden' name='cancel_request' value='true' />
                <input type='submit' value='Cancel request' />
            </form> 
            </p>
        <?php
        if (isset($_GET['show_finish']) && $is_admin && $curr_type == CURRENCY) {
            echo "            <p>" . sprintf(_("Clicking 'Finish request' will mark this request as being %s"),
                                             translate_request_code("FINAL")) . ":</p>\n"; ?>
            <p><?php echo _("Click 'Finish', check to see that it worked (and that the order wasn't cancelled), then make the bank transfer."); ?></p>
            <p>
            <form action='' class='indent_form' method='post'>
                <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
                <input type='hidden' name='finish_request' value='true' />
                <input type='submit' value='<?php echo _("Finish request"); ?>' />
            </form> 
            </p>
            <?php }
        } ?>
    </div> <?php
}
?>
