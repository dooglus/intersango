<?php
require_once 'util.php';

if (isset($_POST['amount']) && isset($_POST['curr_type']))
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
        throw new Error("csrf","csrf token missing");
    }
}

function uk_withdraw($uid, $amount, $curr_type)
{
    $name = post('name_holder');
    $bank = post('name_bank');
    $acc_num = post('account_number');
    $sort_code = post('sort_code');
    syslog(LOG_NOTICE, "name=$name,bank=$bank,acc=$acc_num,sort=$sort_code");
    endlog();

    $query = "
        INSERT INTO requests (req_type, uid, amount, curr_type)
        VALUES ('WITHDR', '$uid', TRUNCATE('$amount', -6), '$curr_type');
    ";
    do_query($query);
    $reqid = mysql_insert_id();
    $query = "
        INSERT INTO uk_requests (reqid, name, bank, acc_num, sort_code)
        VALUES ('$reqid', '$name', '$bank', '$acc_num', '$sort_code');
    ";
    do_query($query);
}

function international_withdraw($uid, $amount, $curr_type)
{
    $iban = post('iban');
    $swift = post('swift');
    syslog(LOG_NOTICE, "iban=$iban,swift=$swift");
    endlog();

    $query = "
        INSERT INTO requests (req_type, uid, amount, curr_type)
        VALUES ('WITHDR', '$uid', TRUNCATE('$amount', -6), '$curr_type');
    ";
    do_query($query);
    $reqid = mysql_insert_id();
    $query = "
        INSERT INTO international_requests (reqid, iban, swift)
        VALUES ('$reqid', '$iban', '$swift');
    ";
    do_query($query);
}

function bitcoin_withdraw($uid, $amount, $curr_type)
{
    $addy = post('address');
    $bitcoin = connect_bitcoin();
    $validaddy = $bitcoin->validateaddress($addy);
    if (!$validaddy['isvalid'])
        throw new Problem('Bitcoin says no', 'That address you supplied was invalid.');
    syslog(LOG_NOTICE, "address=$addy");
    endlog();

    $query = "
        INSERT INTO requests (req_type, uid, amount, curr_type)
        VALUES ('WITHDR', '$uid', TRUNCATE('$amount', -6), '$curr_type');
    ";
    do_query($query);
    $reqid = mysql_insert_id();
    $query = "
        INSERT INTO bitcoin_requests (reqid, addy)
        VALUES ('$reqid', '$addy');
    ";
    do_query($query);
}

function save_details($uid, $amount, $curr_type)
{
    beginlog();
    syslog(LOG_NOTICE, "Withdrawing $amount $curr_type:");
    if ($curr_type == 'AUD') {
        $is_international = post('is_international') == 'true';
        if (!$is_international) {
            uk_withdraw($uid, $amount, $curr_type);
            return true;
        }
        else {
            international_withdraw($uid, $amount, $curr_type);
            return true;
        }
    }
    else if ($curr_type == 'BTC') {
        bitcoin_withdraw($uid, $amount, $curr_type);
        return true;
    }
    else {
        throw Error('Invalid currency', 'You cannot withdraw a currency that does not exist.');
    }
    # should never happen!
    return false;
}

function truncate_num($num)
{
    return substr($num, 0, -6) . '000000';
}

if (isset($_POST['amount']) && isset($_POST['curr_type'])) {
    
    $uid = user_id();
    $amount_disp = post('amount');
    $curr_type = post('curr_type');
    $amount = numstr_to_internal($amount_disp);
    $amount = truncate_num($amount);

    curr_supported_check($curr_type);
    order_worthwhile_check($amount, $amount_disp);
    enough_money_check($amount, $curr_type);

    if (!save_details($uid, $amount, $curr_type))
        throw Error('We had to admit it sometime...', 'Stop trading on thie site. Contact the admin FAST.');
    # actually take the money now
    deduct_funds($amount, $curr_type);
    # request is submitted to the queue for the cron job to actually execute

    echo "<div class='content_box'>\n";
    echo "<h3>Withdraw $curr_type</h3>\n";
    echo "<p>Your request to withdraw $amount_disp $curr_type has been submitted. Visit your <a href='?page=profile'>profile</a> to check on the status of your request.</p>\n";
    echo "</div>\n";
}
else {
?>
    <div class='content_box'>
    <h3>Withdraw AUD (Australian residents)</h3>
    <p>Enter an amount below to submit a withdrawal request. We charge no fee.
    You are responsible for paying any incurred fees. If your deposit 
    is insufficient to cover bank fees then it will be denied.</p>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_name_holder'>Name of account holder</label>
            <input type='text' id='input_name_holder' name='name_holder' maxlength='18' />

            <label for='input_name_bank'>Name of the bank</label>
            <input type='text' id='input_name_bank' name='name_bank' />

            <div id='acc_details'>
                <div id='acc_num'>
                    <label for='input_account_number'>Account number</label>
                    <input type='text' class='input_no_block' id='input_account_number' name='account_number' />
                </div>
                <div id='acc_sort'>
                    <label for='input_sort_code'>BSB</label>
                    <input type='text' id='input_sort_code' name='sort_code' />
                </div>
            </div>

            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' />
            
            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='AUD' />
            <input type='hidden' name='is_international' value='false' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    <p>Allow 3-5 working days for payments to pass through clearing.</p>
    </div>

<!-- DISABLED
    <div class='content_box'>
    <h3>Withdraw AUD (international)</h3>
    <p>Enter an amount below to submit a withdrawal request. A fee of 20 AUD for amounts below 5000 AUD and 35 AUD otherwise, applies. Your bank may charge an additional processing fee on their end.</p>
    <p>Please also contact support@britcoin.co.uk</p>
    <p>
        <form action='' class='indent_form' method='post'>
            <div id='acc_details'>
                <div id='acc_num'>
                    <label for='input_account_number'>IBAN</label>
                    <input type='text' class='input_no_block' id='input_account_number' name='iban' />
                </div>
                <div id='acc_sort'>
                    <label for='input_sort_code'>BIC/SWIFT</label>
                    <input type='text' id='input_sort_code' name='swift' />
                </div>
            </div>

            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' />

            <input type='hidden' name='curr_type' value='AUD' />
            <input type='hidden' name='is_international' value='true' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>
-->

    <div class='content_box'>
    <h3>Withdraw BTC</h3>
    <p>Enter an amount below to withdraw.</p>
    <p>
        <form action='' class='indent_form' method='post'>
            <label for='input_amount'>Amount</label>
            <input type='text' id='input_amount' name='amount' />

            <label for='input_address'>Address</label>
            <input type='text' id='input_address' name='address' />
            
            <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
            <input type='hidden' name='curr_type' value='BTC' />
            <input type='submit' value='Submit' />
        </form>
    </p>
    </div>
<?php
}
?>
