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

function display_request_info_aud($reqid)
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
    if (!$row)
        return;
    echo "<p>Bitcoin address: {$row['addy']}</p>\n";
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
    echo "<p>IBAN: {$row['iban']}</p>\n";
    echo "<p>BIC/SWIFT: {$row['swift']}</p>\n";
}

$reqid = get('reqid');
$uid = user_id();

if (isset($_POST['cancel_request'])) {
    # cancel an order
    if ($is_admin)
        $uid_check = "";
    else
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
    ?><div class='content_box'>
        <h3>Cancelled!</h3>
        <p>Request <?php echo $reqid; ?> is no more.</p>
    </div><?php
}
else {
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
        <h3>Order info</h3>
        <p>
        Request <?php echo $reqid; ?>
        </p>
        <?php
        if ($req_type == 'WITHDR') {
            echo "<p>Withdrawing $amount $curr_type.</p>\n";
        }
        ?>
        <p>
        <?php
        # only one of these will return a result
        display_request_info_aud($reqid);
        display_request_info_btc($reqid);
        display_request_info_intnl($reqid);
        ?>
        </p>
        <p>
        Made <?php echo $timest; ?>
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
        <?php } ?>
    </div> <?php
}
?>

