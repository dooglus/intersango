<?php

function add_funds($uid, $amount, $type)
{
    # eventually plan to move these to prepared mysql statements once the queries become more mature.
    $query = "
        UPDATE purses
        SET
            amount = amount + '$amount'
        WHERE
            uid='$uid'
            AND type='$type';
        ";
    do_query($query);
}

function create_record($our_orderid, $our_amount, $them_orderid, $them_amount)
{
    # record keeping
    $query = "
        INSERT INTO transactions (
            a_orderid,
            a_amount,
            b_orderid,
            b_amount
        ) VALUES (
            '$our_orderid',
            '$our_amount',
            '$them_orderid',
            '$them_amount'
        );
    ";
    do_query($query);
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
    do_query($query);

    # update order that's completing us
    $query = "
        UPDATE orderbook
        SET
            amount = amount - '$them_amount',
            want_amount = want_amount - '$our_amount'
        WHERE
            orderid='$them_orderid';
        ";
    do_query($query);

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
    do_query($query);

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
    do_query($query);

    # perform funding of both accounts
    add_funds($our_uid, $them_amount, $them_type);
    add_funds($them_uid, $our_amount, $our_type);

    create_record($our_orderid, $our_amount, $them_orderid, $them_amount);
}

function return_remaining($orderid, $uid, $amount, $type)
{
    add_funds($uid, $amount, $type);
    # these records indicate returned funds.
    create_record($orderid, $amount, -1, 0);
}

class OrderInfo
{
    public $orderid, $uid, $initial_amount, $amount, $type, $initial_want_amount, $want_amount, $want_type, $status;

    public function __construct($row)
    {
        $this->orderid = $row['orderid'];
        $this->uid = $row['uid'];
        $this->initial_amount = $row['initial_amount'];
        $this->amount = $row['amount'];
        $this->type = $row['type'];
        $this->initial_want_amount = $row['initial_want_amount'];
        $this->want_amount = $row['want_amount'];
        $this->want_type = $row['want_type'];
        $this->status = $row['status'];
    }
}

function fetch_order_info($orderid)
{
    $query = "
        SELECT *
        FROM orderbook
        WHERE orderid='$orderid';
    ";
    $result = do_query($query);
    $row = get_row($result);
    $info = new OrderInfo($row);
    return $info;
}

function fulfill_order($our_orderid)
{
    $our = fetch_order_info($our_orderid);
    if ($our->status != 'OPEN')
        throw new Error('Order not open', "This order can't be fulfilled because it's not available.");

    $query = "
        SELECT *
        FROM orderbook
        WHERE
            status='OPEN'
            AND type='{$our->want_type}'
            AND want_type='{$our->type}'
            AND initial_amount * '{$our->initial_amount}' >= initial_want_amount * '{$our->initial_want_amount}'
            AND uid!='{$our->uid}';
    ";
    $result = do_query($query);
    while ($row = mysql_fetch_array($result)) {
        $them = new OrderInfo($row);
        if ($them->type != $our->want_type || $our->type != $them->want_type)
            throw Error('Problem', 'Urgent problem. Contact the site owner IMMEDIATELY.');
        # $them_amount >= $our_want_amount
        if (gmp_cmp($them->amount, $our->want_amount) != -1) {
            pacman($our->orderid, $our->uid, $our->amount, $our->type, $them->orderid, $them->uid, $our->want_amount, $our->want_type);
            # finished!
            break;
        }
        else {
            # so our amount is bigger than their's. we absorb them.
            # we need to calculate a new want_amount for them based on our current exchange rate.
            # we do this by constructing a new order:
            #    rate = our_amount / our_want_amount
            #    them_new_want = them_amount * rate
            $them->new_want = gmp_mul($them->amount, $our->initial_amount);
            list($them_new_want, $them_return) = gmp_div_qr($them->new_want, $our->initial_want_amount);
            $them_new_want = gmp_strval($them_new_want);
            $them_return = gmp_strval($them_return);
            pacman($them->orderid, $them->uid, $them->amount, $them->type, $our->orderid, $our->uid, $them_new_want, $our->type);
            # return remaining amount... little freebie
            if (gmp_cmp($them_return, 0) != 0)
                return_remaining($them->orderid, $them->uid, $them_return, $them->type);
            # re-update as still haven't finished...
            $our = fetch_order_info($our->orderid);
            # order was closed and our job is done.
            if ($our->status != 'OPEN')
                break;
        }
    }
}

?>

