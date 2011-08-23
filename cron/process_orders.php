<?php
require_once '../util.php';
require_once '../errors.php';

function b_query($query)
{
    echo "$query;\n";
    return do_query($query);
}

# we have two orders which have matched.  one of them partially fills the other
# refer to them as 'partial' and 'filled'
function pacman($filled_orderid,  $filled_uid,  $amount_from_filled,  $filled_type,  $old_filled_commission,
                $partial_orderid, $partial_uid, $amount_from_partial, $partial_type, $old_partial_commission)
{
    echo "    pacman: order $partial_orderid (user $partial_uid, already paid $old_partial_commission) is filling\n";
    echo "            order $filled_orderid (user $filled_uid, already paid $old_filled_commission)\n";
    echo "            by giving ",
        internal_to_numstr($amount_from_partial), " $partial_type for ",
        internal_to_numstr($amount_from_filled), " $filled_type\n\n";

    # close order that's being filled
    $query = "
        UPDATE orderbook
        SET
            amount='0',
            want_amount='0',
            status='CLOSED'
        WHERE
            orderid='$filled_orderid';
        ";
    b_query($query);

    # update the partially filled order
    $query = "
        UPDATE orderbook
        SET
            amount = amount - '$amount_from_partial'
        WHERE
            orderid='$partial_orderid';
        ";
    b_query($query);

    # update the want amount using the initially requested price
    $query = "
        UPDATE orderbook
        SET
            want_amount = 1.0 * amount * initial_want_amount / initial_amount
        WHERE
            orderid='$partial_orderid';
        ";
    b_query($query);

    # it's possible both orders fill each other; if so, close the other one too
    $query = "
        UPDATE orderbook
        SET
            status='CLOSED'
        WHERE
            orderid='$partial_orderid'
            AND amount <= 0;
        ";
    b_query($query);

    // calculate commission
    //   partial_commission is the commission paid on the money received by the partially filled order,
    //     ie. on the money received from the filled order
    $partial_commission = commission_on_type($amount_from_filled,  $filled_type,  $old_partial_commission);
    $filled_commission  = commission_on_type($amount_from_partial, $partial_type, $old_filled_commission);

    // calculate amount remaining after commission
    $partial_minus_commission = gmp_strval(gmp_sub($amount_from_partial, $filled_commission));
    $filled_minus_commission  = gmp_strval(gmp_sub($amount_from_filled,  $partial_commission));

    // perform funding of both accounts
    add_funds($filled_uid,  $partial_minus_commission, $partial_type);
    add_funds($partial_uid, $filled_minus_commission,  $filled_type);

    // take the commission
    take_commission($filled_commission,  $partial_type, $filled_orderid);
    take_commission($partial_commission, $filled_type,  $partial_orderid);

    // record the transaction
    if ($filled_type == 'BTC')
        create_record($partial_orderid, $amount_from_partial, $filled_commission,
                      $filled_orderid,  $amount_from_filled,  $partial_commission);
    else
        create_record($filled_orderid,  $amount_from_filled,  $partial_commission,
                      $partial_orderid, $amount_from_partial, $filled_commission);
}

function fulfill_order($our_orderid)
{
    $our = fetch_order_info($our_orderid);
    if ($our->status != 'OPEN')
        return;
    if ($our->processed)
        throw new Error('Unprocessed', "Shouldn't be here for $our_orderid");

    # Dividing two bignum(20) values only gives us 4 decimal places in the result
    # this can cause us to process the matching orders out of sequence unless we arrange
    # for the quotient to be greater than 1 by putting the bigger value on top.
    #
    # With BTC at around 10 GBP each, I just saw the previous version of this query
    # process 2 orders out of sequence because the values of initial_want_amount / initial_amount
    # for the two orders were 0.09348 and 0.09346, which compare equal to 4 decimal places

    if ($our->initial_amount > $our->initial_want_amount)
        $order_by = "initial_want_amount / initial_amount ASC";
    else
        $order_by = "initial_amount / initial_want_amount DESC";

    $query = "
        SELECT orderid, uid
        FROM orderbook
        WHERE
            status='OPEN'
            AND processed=TRUE
            AND type='{$our->want_type}'
            AND want_type='{$our->type}'
            AND initial_amount * '{$our->initial_amount}' >= initial_want_amount * '{$our->initial_want_amount}'
            AND uid!='{$our->uid}'
        ORDER BY $order_by, timest ASC;
    ";
    wait_for_lock($our->uid);
    $result = b_query($query);
    while ($row = mysql_fetch_array($result)) {
        echo "Found matching ", $row['orderid'], " from user ", $row['uid'], ".\n";
        wait_for_lock($row['uid']);   // lock their account
        $them = fetch_order_info($row['orderid']); // re-fetch their order now that they're locked
        if ($them->status != 'OPEN') {
            echo "order {$them->orderid} was cancelled on us\n";
            release_lock($them->uid);
            continue;
        }

        if ($them->type != $our->want_type || $our->type != $them->want_type)
            throw Error('Problem', 'Urgent problem. Contact the site owner IMMEDIATELY.');
        # echo "  them: orderid {$them->orderid}, uid {$them->uid}, have {$them->amount} {$them->type}, want {$them->want_amount}\n";
        # echo "  us: orderid {$our->orderid}, uid {$our->uid }, have: {$our->amount} {$our->type}, want {$our->want_amount}\n";
        # echo "  them->initial_amount = {$them->initial_amount}, them->initial_want_amount = {$them->initial_want_amount}\n";

        $left = gmp_mul($our->amount, $them->initial_amount);
        $right = gmp_mul($them->amount, $them->initial_want_amount);

        if (gmp_cmp($left, $right) >= 0) {
            # We need to calculate how much of our stuff they can afford at their price
            # we ignore the remainder - it's totally insignificant.
            $them->new_want = gmp_strval(gmp_div($right, $them->initial_amount));
            echo "    we swallow them; they can afford {$them->new_want} from us\n";

            pacman($them->orderid, $them->uid, $them->amount,   $them->type, $them->commission,
                   $our->orderid,  $our->uid,  $them->new_want, $our->type,  $our->commission);
            release_lock($them->uid);

            # re-update as still haven't finished...
            # info needed for any further transactions
            $our = fetch_order_info($our->orderid);
            # order was closed and our job is done.
            if ($our->status != 'OPEN')
                break;
        }
        else {
            # We need to calculate how much of their stuff we can afford at their price
            # we ignore the remainder - it's totally insignificant.
            $our->new_want = gmp_strval(gmp_div($left, $them->initial_want_amount));
            echo "    they swallow us; we can afford {$our->new_want} from them\n";

            pacman($our->orderid,  $our->uid,  $our->amount,   $our->type,      $our->commission,
                   $them->orderid, $them->uid, $our->new_want, $our->want_type, $them->commission);
            release_lock($them->uid);
            break;
        }
    }
    release_lock($our->uid);
}

function process()
{
    do_query("SET div_precision_increment = 8");

    // find and cancel any active orders from users with negative BTC or AUD balances
    // this should never happen unless someone is trying to double-spend their balance
    $query = "
        SELECT orderid, orderbook.amount as amount, orderbook.type, orderbook.uid as uid
        FROM orderbook
        JOIN purses
        ON orderbook.uid = purses.uid
        WHERE
            status != 'CLOSED' AND
            status != 'CANCEL' AND
            purses.amount < 0
        GROUP BY orderid
        ";
    $result = b_query($query);
    while ($row = mysql_fetch_array($result)) {
        $orderid = $row['orderid'];
        $amount = $row['amount'];
        $type = $row['type'];
        $uid = $row['uid'];
        try {
            echo "cancelling order $orderid (spend ", internal_to_numstr($amount), " $type for user $uid) due to negative balance\n";
            wait_for_lock($uid);
            $query = "
    UPDATE orderbook
    SET status = 'CANCEL'
    WHERE orderid = '$orderid'
            ";
            b_query($query);
            add_funds($uid, $amount, $type);

            // these records indicate returned funds.
            create_record($orderid, $amount, 0,
                          0,        -1,      0);
            release_lock($uid);
        }
        catch (Error $e) {
            if ($e->getTitle() == 'Lock Error')
                echo "can't get lock for $uid\n";
            else
                throw $e;
        }
    }

    $query = "
        SELECT orderid
        FROM orderbook
        WHERE processed=FALSE
        ORDER BY timest ASC
    ";
    $result = b_query($query);
    while ($row = mysql_fetch_array($result)) {
        $orderid = $row['orderid'];
        echo "Processing $orderid...\n";
        fulfill_order($orderid);
        echo "Completed.\n\n";
        $query = "
            UPDATE orderbook
            SET processed=TRUE
            WHERE orderid='$orderid'
        ";
        b_query($query);
    }
}

try {
    check_frozen();
    process();
}
catch (Error $e) {
    report_exception($e, SEVERITY::ERROR);
    // Same as below, but flag + log this for review,
    echo "\nError: \"{$e->getTitle()}\"\n  {$e->getMessage()}\n";
}
catch (Problem $e) {
    echo "\nProblem: \"{$e->getTitle()}\"\n  {$e->getMessage()}\n";
}
catch (Exception $e) {
    echo "\nException: \"{$e->getTitle()}\"\n  {$e->getMessage()}\n";
}
?>
