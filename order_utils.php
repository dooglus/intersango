<?php

require_once 'util.php';

function place_order($have_amount_disp, $have_currency,
                     $want_amount_disp, $want_currency)
{
    global $is_logged_in;

    $have_currency = strtoupper($have_currency);
    $want_currency = strtoupper($want_currency);

    curr_supported_check($have_currency);
    curr_supported_check($want_currency);

    // convert for inclusion into database
    $have_amount = numstr_to_internal($have_amount_disp);
    $want_amount = numstr_to_internal($want_amount_disp);

    order_worthwhile_check($have_amount, $have_amount_disp, MINIMUM_HAVE_AMOUNT);
    order_worthwhile_check($want_amount, $want_amount_disp, MINIMUM_WANT_AMOUNT);

    enough_money_check($have_amount, $have_currency);

    do_query("START TRANSACTION");

    // deduct money from their account
    deduct_funds($have_amount, $have_currency);

    // add the money to the order book
    $query = "
        INSERT INTO orderbook (
            uid,
            initial_amount,
            amount,
            type,
            initial_want_amount,
            want_amount,
            want_type)
        VALUES (
            '$is_logged_in',
            '$have_amount',
            '$have_amount',
            '$have_currency',
            '$want_amount',
            '$want_amount',
            '$want_currency');
    ";
    $result = do_query($query);
    $orderid = mysql_insert_id();
    do_query("COMMIT");

    return $orderid;
}

function cancel_order($orderid, $uid)
{
    // cancel an order
    $query = "
        UPDATE orderbook
        SET status='CANCEL'
        WHERE
            orderid='$orderid'
            AND uid='$uid'
            AND status='OPEN'
    ";
    do_query($query);

    if (mysql_affected_rows() != 1) {
        if (mysql_affected_rows() > 1) 
            throw new Error('Serious...', 'More rows updated than should be. Contact the sysadmin ASAP.');
        else if (mysql_affected_rows() == 0) 
            throw new Problem(_('Cannot...'), _('Your order got bought up before you were able to cancel.'));
        else 
            throw new Error('Serious...', 'Internal error. Contact sysadmin ASAP.');
    }

    // Refetch order in case something has happened.
    $info = fetch_order_info($orderid);

    if ($uid != $info->uid)
        throw new Error('Permission...', '... Denied! Now GTFO.');

    add_funds($info->uid, $info->amount, $info->type);
    // these records indicate returned funds.
    create_record($orderid, $info->amount, 0,
		  0,        -1,            0);
    addlog(LOG_RESULT, "  cancelled order $orderid");
}

function get_orders()
{
    global $is_logged_in;

    $result = do_query("
        SELECT
            orderid, initial_amount, amount, type, initial_want_amount, want_amount, want_type
        FROM
            orderbook
        WHERE
            status = 'OPEN'
        AND
            uid = $is_logged_in
    ");

    $orders = array();
    while ($row = mysql_fetch_array($result)) {
        $orderid       = $row['orderid'];
        $have_amount   = $row['amount'];
        $have_currency = $row['type'];
        $want_amount   = $row['want_amount'];
        $want_currency = $row['want_type'];

        if ($have_currency == 'BTC')
            $text = sprintf("%s %s %s %s %s %s",
                            _("Sell"),
                            internal_to_numstr($have_amount, BTC_PRECISION),
                            $have_currency,
                            _("for"),
                            internal_to_numstr($want_amount, FIAT_PRECISION),
                            $want_currency);
        else
            $text = sprintf("%s %s %s %s %s %s",
                            _("Buy"),
                            internal_to_numstr($want_amount, BTC_PRECISION),
                            $want_currency,
                            _("for"),
                            internal_to_numstr($have_amount, FIAT_PRECISION),
                            $have_currency);

        array_push($orders, array('orderid'             => $orderid,
                                  'text'                => $text,
                                  'have_amount'         => internal_to_numstr($have_amount),
                                  'have_currency'       => $row['type'],
                                  'want_amount'         => internal_to_numstr($want_amount),
                                  'want_currency'       => $want_currency));
    }

    return $orders;
}

?>
