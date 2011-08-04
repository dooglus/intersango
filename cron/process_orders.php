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
function pacman($filled_orderid,  $filled_uid,  $amount_from_filled,  $filled_type,
                $partial_orderid, $partial_uid, $amount_from_partial, $partial_type)
{
    echo "    pacman: order $partial_orderid (user $partial_uid) is filling order $filled_orderid (user $filled_uid) by giving $amount_from_partial $partial_type for $amount_from_filled $filled_type\n\n";

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

    # perform funding of both accounts
    add_funds($filled_uid, $amount_from_partial, $partial_type);
    add_funds($partial_uid, $amount_from_filled, $filled_type);

    create_record($filled_orderid, $amount_from_filled, $partial_orderid, $amount_from_partial);
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
        SELECT *, timest AS timest_format
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
    $result = b_query($query);
    while ($row = mysql_fetch_array($result)) {
        $them = new OrderInfo($row);
        echo "Found matching {$them->orderid}.\n";
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

            pacman($them->orderid, $them->uid, $them->amount,   $them->type,
                   $our->orderid,  $our->uid,  $them->new_want, $our->type);

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

            pacman($our->orderid,  $our->uid,  $our->amount,   $our->type,
                   $them->orderid, $them->uid, $our->new_want, $our->want_type);
            break;
        }
    }
}

function process()
{
    do_query("LOCK TABLES orderbook WRITE, purses WRITE, transactions WRITE");
    do_query("SET div_precision_increment = 8");

    // find and cancel any active orders from users with negative BTC or AUD balances
    // this should never happen unless someone is trying to double-spend their balance
    $query = "
        SELECT orderid
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
        $query = "
            UPDATE orderbook
            SET status=CANCEL
            WHERE orderid='$orderid'
        ";
        b_query($query);
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
    do_query("UNLOCK TABLES");
}

process();

?>
