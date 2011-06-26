<?php
require '../util.php';
require '../errors.php';

function b_query($query)
{
    echo "$query;\n";
    return do_query($query);
}

function pacman($our_orderid, $our_uid, $our_amount, $our_type, $them_orderid, $them_uid, $them_amount, $them_type)
{
    # close order that's being absorbed
    $query = "
        UPDATE orderbook
        SET
            amount='0',
            want_amount='0',
            status='CLOSED'
        WHERE
            orderid='$our_orderid';
        ";
    b_query($query);

    # update order that's completing us
    $query = "
        UPDATE orderbook
        SET
            amount = amount - '$them_amount',
            want_amount = want_amount - '$our_amount'
        WHERE
            orderid='$them_orderid';
        ";
    b_query($query);

    # close the previous order if it's been fulfilled
    $query = "
        UPDATE orderbook
        SET
            status='CLOSED'
        WHERE
            orderid='$them_orderid'
            AND (
                amount <= 0
                OR want_amount <= 0
                );
        ";
    b_query($query);

    # if them transaction was closed then we add the remainder back to their purse
    $query = "
        UPDATE purses AS purses
        JOIN orderbook AS orderbook
        ON purses.uid=orderbook.uid
        SET
            purses.amount = purses.amount + orderbook.amount
        WHERE
            purses.uid='$them_uid'
            AND purses.type='$them_type'
            AND orderbook.orderid='$them_orderid'
            AND orderbook.status='CLOSED';
        ";
    b_query($query);

    # perform funding of both accounts
    add_funds($our_uid, $them_amount, $them_type);
    add_funds($them_uid, $our_amount, $our_type);

    create_record($our_orderid, $our_amount, $them_orderid, $them_amount);
}

function fulfill_order($our_orderid)
{
    $our = fetch_order_info($our_orderid);
    if ($our->status != 'OPEN')
        return;
    if ($our->processed)
        throw new Error('Unprocessed', "Shouldn't be here for $our_orderid");

    $query = "
        SELECT *, timest AS timest_format
        FROM orderbook
        WHERE
            status='OPEN'
            AND type='{$our->want_type}'
            AND want_type='{$our->type}'
            AND initial_amount * '{$our->initial_amount}' >= initial_want_amount * '{$our->initial_want_amount}'
            AND uid!='{$our->uid}'
        ORDER BY initial_want_amount / initial_amount ASC, timest ASC;
    ";
    $result = b_query($query);
    while ($row = mysql_fetch_array($result)) {
        $them = new OrderInfo($row);
        echo "Found matching {$them->orderid}.\n";
        if ($them->type != $our->want_type || $our->type != $them->want_type)
            throw Error('Problem', 'Urgent problem. Contact the site owner IMMEDIATELY.');
        # $them_amount >= $our_want_amount
        if (gmp_cmp($our->amount, $them->want_amount) >= 0) {
            echo "We swallow them.\n";
            pacman($them->orderid, $them->uid, $them->amount, $them->type, $our->orderid, $our->uid, $them->want_amount, $our->type);

            # re-update as still haven't finished...
            # info needed for any further transactions
            $our = fetch_order_info($our->orderid);
            # order was closed and our job is done.
            if ($our->status != 'OPEN')
                break;
        }
        else {
            echo "They swallow us.\n";
            # so their amount is bigger than ours. they absorb us.
            # we need to calculate a new want_amount for us based on their exchange rate.
            # we do this by constructing a new order:
            #    rate = their_amount / their_want_amount
            #    our_new_want = our_amount * rate
            $our->new_want = gmp_mul($our->amount, $them->initial_amount);
            list($our_new_want, $our_remain) = gmp_div_qr($our->new_want, $them->initial_want_amount);
            $our_new_want = gmp_strval($our_new_want);
            pacman($our->orderid, $our->uid, $our->amount, $our->type, $them->orderid, $them->uid, $our_new_want, $our->want_type);

            # we ignore the disparity which is our_remain / initial_want_amount
            # max(remain) = iwant - 1
            # max(disp)   = (iwant - 1) / iwant
            #             = 1 - 1/iwant
            # disp < 1  ALWAYS
            # Therefore it does not matter and is totally insignificant.

            # finished!
            break;
        }
    }
}

function process()
{
    do_query("LOCK TABLES orderbook WRITE, purses WRITE, transactions WRITE");
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

