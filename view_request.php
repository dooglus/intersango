<?php
require 'util.php';

function display_request_info_gbp($uid, $reqid)
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
function display_request_info_btc($uid, $reqid)
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
function display_request_info_intnl($uid, $reqid)
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
            AND requests.uid='$uid'
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
    $query = "
        SELECT
            req_type,
            amount,
            curr_type,
            DATE_FORMAT(timest, '%H:%i %d/%m/%y') AS timest,
            status
        FROM requests
        WHERE reqid='$reqid' AND uid='$uid'
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
            $req_type = translate_request_type($req_type);
            echo "<p>Withdrawing $amount $curr_type.</p>\n";
        }
        ?>
        <p>
        <?php
        # only one of these will return a result
        display_request_info_gbp($uid, $reqid);
        display_request_info_btc($uid, $reqid);
        display_request_info_intnl($uid, $reqid);
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
                <input type='hidden' name='cancel_request' value='true' />
                <input type='submit' value='Cancel request' />
            </form> 
            </p>
        <?php } ?>
    </div> <?php
}
?>

