<?php

function statement_checked($value)
{
    return $value ? " checked='checked'" : "";
}

function statement_checkbox($name, $value, $label, $args = false)
{
    if ($args)
        $label = sprintf('<a href="?page=statement&%s&form=1&%s=1">%s</a>', $args, $name, $label);

    return sprintf("<input onChange='this.form.submit()' type='checkbox' name='%s' value='1'%s />%s&nbsp;\n",
                   $name, statement_checked($value), $label);
}

function deposited_or_withdrawn($deposit, $withdraw)
{
    $net = gmp_sub($deposit, $withdraw);
    $abs = gmp_abs($net);

    if (gmp_cmp($net, 0) < 0)
        $word = _("withdrawn");
    else
        $word = _("deposited");

    return array($abs, $word);
}

function bought_or_sold($bought, $bought_for, $sold, $sold_for)
{
    if (gmp_cmp($bought, $sold) < 0) {
        $word = _("sold");
        $net     = gmp_sub($sold,     $bought    );
        $net_for = gmp_sub($sold_for, $bought_for);
    } else {
        $word = _("bought");
        $net     = gmp_sub($bought,     $sold    );
        $net_for = gmp_sub($bought_for, $sold_for);
    }

    return array($word, $net, $net_for);
}

function trade_price($btc, $fiat, $verbose = false) {
    if (gmp_cmp($btc, 0) == 0)
        return '';
    if ($verbose)
        return "(" . _("price") . " " . fiat_and_btc_to_price(gmp_strval($fiat), gmp_strval($btc)) . ")";
    else
        return fiat_and_btc_to_price(gmp_strval($fiat), gmp_strval($btc));
}

function show_statement_summary($title,
                                $total_fiat_deposit, $total_fiat_withdrawal, $total_btc_deposit, $total_btc_withdrawal,
                                $total_fiat_got, $total_fiat_given, $total_btc_got, $total_btc_given)
{
    echo "<div class='content_box'>\n";
    echo "<h3>$title</h3>\n";

    list ($total_net_fiat, $total_net_fiat_word) = deposited_or_withdrawn($total_fiat_deposit, $total_fiat_withdrawal);
    list ($total_net_btc, $total_net_btc_word)   = deposited_or_withdrawn($total_btc_deposit,  $total_btc_withdrawal);

    list ($total_trade_word, $total_trade_btc, $total_trade_fiat) = bought_or_sold($total_btc_got, $total_fiat_given,
                                                                                   $total_btc_given, $total_fiat_got);

    $total_bought_price = trade_price($total_btc_got,   $total_fiat_given, 'verbose');
    $total_sold_price   = trade_price($total_btc_given, $total_fiat_got,   'verbose');
    $total_net_price    = trade_price($total_trade_btc, $total_trade_fiat, 'verbose');

    echo "<table class='display_data'>\n";
    foreach (array(
                 _("total") . " " . CURRENCY . " " . _("deposited")  => internal_to_numstr($total_fiat_deposit,    FIAT_PRECISION),
                 _("total") . " " . CURRENCY . " " . _("withdrawn")  => internal_to_numstr($total_fiat_withdrawal, FIAT_PRECISION),
                 _("net") . " " . CURRENCY . " $total_net_fiat_word" => internal_to_numstr($total_net_fiat,        FIAT_PRECISION),
                 ""                      => "",
                 _("total") . " BTC " . _("deposited") => internal_to_numstr($total_btc_deposit,    BTC_PRECISION ),
                 _("total") . " BTC " . _("withdrawn") => internal_to_numstr($total_btc_withdrawal, BTC_PRECISION ),
                 _("net") . " BTC $total_net_btc_word" => internal_to_numstr($total_net_btc,        BTC_PRECISION ),
                 " "                     => "",
                 ) as $a => $b)
        echo "<tr><td>$a</td><td class='right'>$b</td></tr>\n";
    foreach (array(
                 _("total") . " BTC " . _("bought") => array(internal_to_numstr($total_btc_got,     BTC_PRECISION) . " BTC", _("for"),
                                                             internal_to_numstr($total_fiat_given, FIAT_PRECISION) . " " . CURRENCY,
                                                             $total_bought_price),
                 _("total") . " BTC " . _("sold")   => array(internal_to_numstr($total_btc_given,   BTC_PRECISION) . " BTC", _("for"),
                                                             internal_to_numstr($total_fiat_got,   FIAT_PRECISION) . " " . CURRENCY,
                                                             $total_sold_price),
                 _("net")   . " BTC " . $total_trade_word => array(internal_to_numstr($total_trade_btc,  BTC_PRECISION) . " BTC", _("for"),
                                                             internal_to_numstr($total_trade_fiat,       FIAT_PRECISION) . " " . CURRENCY,
                                                             $total_net_price),
                 ) as $a => $b) {
        echo "<tr><td>$a</td>";
        foreach ($b as $c)
            echo "<td class='right'>$c</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    echo "</div>";
}

function show_balances_in_statement($description, $btc, $fiat, $all_users, $show_prices, $show_increments)
{
    echo "<tr>";
    echo "<td></td>";
    if ($all_users)
        echo "<td></td>";
    echo "<td>" . $description . "</td>";
    if ($show_prices)
        echo "<td></td>";
    if ($show_increments)
        echo "<td></td>";
    printf("<td class='right'>%s</td>", internal_to_numstr($btc,  BTC_PRECISION));
    if ($show_increments)
        echo "<td></td>";
    printf("<td class='right'>%s</td>", internal_to_numstr($fiat,  FIAT_PRECISION));
    echo "</tr>\n";
}

function show_statement($userid, $interval = 'forever',
                        $from_zero,
                        $deposit_btc, $withdraw_btc, $deposit_fiat, $withdraw_fiat, $buy, $sell)
{
    global $is_logged_in, $is_admin;

    if ($userid)
        $specified_user = true;
    else {
        $specified_user = false;
        $userid = $is_logged_in;
    }

    $show_increments = false;
    $show_prices = true;

    echo "<div class='content_box'>\n";

    $all_users = ($userid == 'all');

    $deposit_address = $create_timestamp = false;
    if ($all_users) {
        echo "<h3>" . _("Statement for All Users") . "</h3>\n";
        $check_stuff = "";
    } else {
        $openid = get_openid_for_user($userid);
        echo "<h3>" . sprintf(_("Statement for UID %s"), $userid) . "</h3>\n";
        $check_stuff = "uid='$userid' AND ";
        if ($is_admin) {
            $create_timestamp = get_account_creation_timest_for_user($userid);
            try {
                $deposit_address = bitcoin_get_account_address($userid);
            } catch (Exception $e) { }
        }
    }

    echo ("<form method='get'>\n" .
          "<p>\n" .
          _("Show entries from ") . "\n" .
          "<input type='hidden' name='page' value='statement' />\n");
    echo "<select onChange='this.form.submit()' name='interval'>\n";

    foreach (array('4 hour'  => _('the last 4 hours' ),
                   '12 hour' => _('the last 12 hours'),
                   '1 day'   => _('the last 24 hours'),
                   '3 day'   => _('the last 3 days'  ),
                   '1 week'  => _('the last 7 days'  ),
                   '1 month' => _('the last month'   ),
                   '2 month' => _('the last 2 months'),
                   '3 month' => _('the last 3 months'),
                   '6 month' => _('the last 6 months'),
                   '1 year'  => _('the last year'    ),
                   'forever' => _('forever'          )) as $key => $text) {
        printf("<option %s value='%s'>%s</option>\n",
               ($interval == $key) ? "selected='selected'" : "",
               $key, $text);
    }

    echo "</select>\n";
    if ($is_admin) {
        echo " for <select onChange='this.form.submit()' name='user'>\n";
        if ($all_users) {
            printf("<option value='$is_logged_in'>%s</option>\n", _("my account"));
            printf("<option value='all' selected='selected'>all users</option>\n");
        } else {
            if ($userid != $is_logged_in)
                printf("<option value='$is_logged_in'>%s</option>\n", _("my account"));
            printf("<option value='$userid' selected='selected'>%s</option>\n", $userid == $is_logged_in ? _("my account") : "UID $userid");
            echo "<option value='all'>all users</option>\n";
        }
        echo "</select>\n";
        echo " or UID or OpenID: ";
        echo "<input class='nline' type='text' name='uid'>\n";
    }

    $use_interval = ($interval != 'forever');

    $args = $specified_user ? "user=$userid&" : "";
    $args .= "interval=$interval";
    if ($from_zero) $args .= "&fromz=1";

    echo "<input type='hidden' name='form' value='1' /><br />\n";
    echo statement_checkbox('dbtc',  $deposit_btc,   _("Deposit")  . " " . "BTC",    $args);
    echo statement_checkbox('wbtc',  $withdraw_btc,  _("Withdraw") . " " . "BTC",    $args);
    echo statement_checkbox('dfiat', $deposit_fiat,  _("Deposit")  . " " . CURRENCY, $args);
    echo statement_checkbox('wfiat', $withdraw_fiat, _("Withdraw") . " " . CURRENCY, $args);
    echo statement_checkbox('bbtc',  $buy,           _("Buy")      . " " . "BTC",    $args);
    echo statement_checkbox('sbtc',  $sell,          _("Sell")     . " " . "BTC",    $args);
    if ($use_interval)
        echo statement_checkbox('fromz', $from_zero,     _("Start at Zero"));
    else if ($from_zero)
        echo "<input type='hidden' name='fromz' value='1' />\n";

    echo "</p>\n";
    echo "</form>\n";

    if (!$all_users) {
        echo "<p>" . _("OpenID") . ": <a href=\"$openid\">$openid</a></p>\n";
        if ($deposit_address)
            echo "<p>" . _("Deposit Address") . ": $deposit_address</p>\n";
    }

    $query = "
        SELECT
            uid,
            txid, a_orderid AS orderid,
            a_amount AS gave_amount, '" . CURRENCY . "' AS gave_curr,
            (b_amount-b_commission) AS got_amount,  'BTC' AS got_curr,
            NULL as reqid,  NULL as req_type,
            NULL as amount, NULL as curr_type, NULL as addy, NULL as voucher, NULL as final, NULL as bank, NULL as acc_num,
            " . sql_format_date('transactions.timest') . " AS date,
            transactions.timest as timest, " .
            ($use_interval ? "transactions.timest > NOW() - INTERVAL $interval" : "1") . " AS new
        FROM
            transactions
        JOIN
            orderbook
        ON
            orderbook.orderid = transactions.a_orderid
        WHERE
            $check_stuff
            b_amount != -1

    UNION

        SELECT
            uid,
            txid, b_orderid AS orderid,
            b_amount AS gave_amount, 'BTC' AS gave_curr,
            (a_amount-a_commission) AS got_amount,  '" . CURRENCY . "' AS got_curr,
            NULL, NULL,
            NULL, NULL, NULL, NULL, NULL, NULL, NULL,
            " . sql_format_date('transactions.timest') . " AS date,
            transactions.timest as timest, " .
            ($use_interval ? "transactions.timest > NOW() - INTERVAL $interval" : "1") . " AS new
        FROM
            transactions
        JOIN
            orderbook
        ON
            orderbook.orderid=transactions.b_orderid
        WHERE
            $check_stuff
            b_amount != -1

    UNION

        SELECT
            uid,
            NULL, NULL,
            NULL, NULL,
            NULL, NULL,
            requests.reqid,  req_type,
            amount, curr_type, addy, CONCAT(prefix, '-...') as voucher, status = 'FINAL', bank, acc_num,
            " . sql_format_date('timest') . " AS date,
            timest, " .
            ($use_interval ? "timest > NOW() - INTERVAL $interval" : "1") . " AS new
        FROM
            requests
        LEFT JOIN
            bitcoin_requests
        ON
            requests.reqid = bitcoin_requests.reqid
        LEFT JOIN
            voucher_requests
        ON
            (requests.reqid = voucher_requests.reqid OR
             requests.reqid = voucher_requests.redeem_reqid)
        LEFT JOIN
            uk_requests
        ON
            requests.reqid = uk_requests.reqid
        WHERE
            $check_stuff
            status != 'CANCEL'

    ORDER BY
        timest, txid, got_curr
    ";

    $first = true;
    $result = do_query($query);
    $fiat = $btc = numstr_to_internal(0);

    $total_fiat_deposit = $total_fiat_withdrawal = $total_btc_deposit = $total_btc_withdrawal = numstr_to_internal(0);
    $total_fiat_got = $total_fiat_given = $total_btc_got = $total_btc_given = numstr_to_internal(0);
    $period_fiat_deposit = $period_fiat_withdrawal = $period_btc_deposit = $period_btc_withdrawal = numstr_to_internal(0);
    $period_fiat_got = $period_fiat_given = $period_btc_got = $period_btc_given = numstr_to_internal(0);

    echo "<table class='display_data'>\n";
    echo "<tr>";
    echo "<th>" . _("Date") . "</th>";
    if ($all_users)
        echo "<th>" . _("User") . "</th>";
    echo "<th>" . _("Description") . "</th>";
    if ($show_prices)
        echo "<th class='right'>" . _("Price") . "</th>";
    if ($show_increments)
        echo "<th class='right'>+/-</th>";
    echo "<th class='right'>BTC</th>";
    if ($show_increments)
        echo "<th class='right'>+/-</th>";
    echo "<th class='right'>" . CURRENCY . "</th>";
    echo "</tr>\n";

    if ($create_timestamp)
        printf("<tr><td>%s</td><td>%s</td></tr>\n",
               $create_timestamp,
               _("Create Account"));

    $all_final = true;
    while ($row = mysql_fetch_array($result)) {

        $new = $row['new'];
        $uid = $row['uid'];
        $date = $row['date'];

        if ($first && $new) {
            if ($from_zero)
                $btc = $fiat = numstr_to_internal(0);

            show_balances_in_statement(_("Opening Balances"), $btc, $fiat, $all_users, $show_prices, $show_increments);
            $first = false;
        }

        if (isset($row['txid'])) { /* buying or selling */
            $txid = $row['txid'];
            $orderid = $row['orderid'];
            $gave_amount = $row['gave_amount'];
            $gave_curr = $row['gave_curr'];
            $got_amount = $row['got_amount'];
            $got_curr = $row['got_curr'];

            if ($got_curr == 'BTC') { /* buying BTC */
                if ($buy) {
                    $fiat = gmp_sub($fiat, $gave_amount);
                    $btc = gmp_add($btc, $got_amount);
                }

                $total_btc_got    = gmp_add($total_btc_got   , $got_amount );
                $total_fiat_given = gmp_add($total_fiat_given, $gave_amount);

                $got_str  = internal_to_numstr($got_amount,  BTC_PRECISION);
                $gave_str = internal_to_numstr($gave_amount, FIAT_PRECISION);

                if ($new && $buy) {
                    $period_btc_got    = gmp_add($period_btc_got   , $got_amount );
                    $period_fiat_given = gmp_add($period_fiat_given, $gave_amount);

                    if (string_is_zero($got_str) && string_is_zero($gave_str))
                        continue;

                    echo "<tr><td>$date</td>";
                    if ($all_users) echo active_table_cell_link_to_user_statement($uid, $interval);

                    active_table_cell_for_order(sprintf(_("Buy %s %s for %s %s"),
                                                        $got_str,  $got_curr,
                                                        $gave_str, $gave_curr),
                                                $orderid);
                    if ($show_prices)
                        printf("<td>%s</td>", trade_price($got_amount, $gave_amount));
                    if ($show_increments)
                        printf("<td class='right'>+ %s</td>", $got_str);
                    printf("<td class='right'> %s</td>",  internal_to_numstr($btc, BTC_PRECISION));
                    if ($show_increments)
                        printf("<td class='right'>- %s</td>", $gave_str);
                    printf("<td class='right'> %s</td>",  internal_to_numstr($fiat, FIAT_PRECISION));
                    echo "</tr>\n";
                }
            } else {            /* selling BTC */
                if ($sell) {
                    $fiat = gmp_add($fiat, $got_amount);
                    $btc = gmp_sub($btc, $gave_amount);
                }

                $total_fiat_got  = gmp_add($total_fiat_got , $got_amount );
                $total_btc_given = gmp_add($total_btc_given, $gave_amount);

                $gave_str = internal_to_numstr($gave_amount, BTC_PRECISION);
                $got_str  = internal_to_numstr($got_amount,  FIAT_PRECISION);

                if ($new && $sell) {
                    $period_fiat_got  = gmp_add($period_fiat_got , $got_amount );
                    $period_btc_given = gmp_add($period_btc_given, $gave_amount);

                    if (string_is_zero($got_str) && string_is_zero($gave_str))
                        continue;

                    echo "<tr><td>$date</td>";
                    if ($all_users) echo active_table_cell_link_to_user_statement($uid, $interval);

                    active_table_cell_for_order(sprintf(_("Sell %s %s for %s %s"),
                                                        $gave_str, $gave_curr,
                                                        $got_str,  $got_curr),
                                                $orderid);
                    if ($show_prices)
                        printf("<td>%s</td>", trade_price($gave_amount, $got_amount));
                    if ($show_increments)
                        printf("<td class='right'>-%s</td>", $gave_str);

                    // don't show balances between pairs of buy and sell rows if we're showing buy as well as sell
                    printf("<td class='right'>%s</td>", ($all_users && $buy) ? "" : internal_to_numstr($btc, BTC_PRECISION));
                    if ($show_increments)
                        printf("<td class='right'>+%s</td>", $got_str);
                    printf("<td class='right'>%s</td>", ($all_users && $buy) ? "" : internal_to_numstr($fiat, FIAT_PRECISION));
                    echo "</tr>\n";
                }
            }
        } else {                /* withdrawal or deposit */
            $reqid = $row['reqid'];
            $req_type = $row['req_type'];
            $amount = $row['amount'];
            $curr_type = $row['curr_type'];
            $voucher = $row['voucher'];
            $final = $row['final'];
            // echo "final is $final<br/>\n";

            $show = (($req_type == 'DEPOS' && (($curr_type == 'BTC' && $deposit_btc) ||
                                               ($curr_type != 'BTC' && $deposit_fiat))) ||
                     ($req_type != 'DEPOS' && (($curr_type == 'BTC' && $withdraw_btc) ||
                                               ($curr_type != 'BTC' && $withdraw_fiat))));

            if ($new && $show) {
                echo "<tr><td>$date</td>";
                if ($all_users) echo active_table_cell_link_to_user_statement($uid, $interval);
            }

            if (!$final)
                $all_final = false;

            if ($req_type == 'DEPOS') { /* deposit */
                $title = '';
                if ($voucher)
                    $title = sprintf(_("from voucher") . " &quot;%s&quot;", $voucher);

                if ($curr_type == 'BTC') { /* deposit BTC */
                    if ($show)
                        $btc = gmp_add($btc, $amount);

                    $total_btc_deposit = gmp_add($total_btc_deposit, $amount);
                    
                    if ($new && $show) {
                        $period_btc_deposit = gmp_add($period_btc_deposit, $amount);

                        active_table_cell_for_request(sprintf("<strong title='%s'>%s%s %s BTC%s</strong>",
                                                              $title,
                                                              $final ? "" : "* ",
                                                              $voucher ? _("Redeem voucher") . ":" : _("Deposit"),
                                                              internal_to_numstr($amount, BTC_PRECISION),
                                                              $final ? "" : " *"),
                                                      $reqid);
                        if ($show_prices)
                            printf("<td></td>");
                        if ($show_increments)
                            printf("<td class='right'>+%s</td>", internal_to_numstr($amount, BTC_PRECISION));
                        printf("<td class='right'>%s</td>", internal_to_numstr($btc, BTC_PRECISION));
                        if ($show_increments)
                            printf("<td></td>");
                        printf("<td></td>");
                    }
                } else {        /* deposit FIAT */
                    if ($show)
                        $fiat = gmp_add($fiat, $amount);

                    $total_fiat_deposit = gmp_add($total_fiat_deposit, $amount);

                    if ($new && $show) {
                        $period_fiat_deposit = gmp_add($period_fiat_deposit, $amount);

                        active_table_cell_for_request(sprintf("<strong title='%s'>%s%s %s %s%s</strong>",
                                                              $title,
                                                              $final ? "" : "* ",
                                                              $voucher ? _("Redeem voucher") . ":" : _("Deposit"),
                                                              internal_to_numstr($amount, FIAT_PRECISION),
                                                              CURRENCY,
                                                              $final ? "" : " *"),
                                                      $reqid);
                        if ($show_prices)
                            printf("<td></td>");
                        if ($show_increments)
                            printf("<td></td>");
                        printf("<td></td>");
                        if ($show_increments)
                            printf("<td class='right'>+%s</td>", internal_to_numstr($amount, FIAT_PRECISION));
                        printf("<td class='right'>%s</td>", internal_to_numstr($fiat, FIAT_PRECISION));
                    }
                }
            } else {            /* withdrawal */
                if ($curr_type == 'BTC') { /* withdraw BTC */
                    if ($show)
                        $btc = gmp_sub($btc, $amount);

                    $total_btc_withdrawal = gmp_add($total_btc_withdrawal, $amount);

                    if ($new && $show) {
                        $period_btc_withdrawal = gmp_add($period_btc_withdrawal, $amount);

                        $addy = $row['addy'];
                        if ($addy)
                            $title = sprintf(_("to Bitcoin address") . " &quot;%s&quot;", $addy);
                        else if ($voucher) {
                            $title = sprintf(_("to %svoucher") . " &quot;%s&quot;",
                                             $final ? "" : (_("unredeemed") . " "),
                                             $voucher);
                        }
                    
                        active_table_cell_for_request(sprintf("<strong title='%s'>%s%s %s BTC%s</strong>",
                                                              $title,
                                                              $final ? "" : "* ",
                                                              $voucher ? _("Create voucher") . ":" : _("Withdraw"),
                                                              internal_to_numstr($amount, BTC_PRECISION),
                                                              $final ? "" : " *"),
                                                      $reqid);
                        if ($show_prices)
                            printf("<td></td>");
                        if ($show_increments)
                            printf("<td class='right'>-%s</td>", internal_to_numstr($amount, BTC_PRECISION));
                        printf("<td class='right'>%s</td>", internal_to_numstr($btc, BTC_PRECISION));
                        if ($show_increments)
                            printf("<td></td>");
                        printf("<td></td>");
                    }
                } else {        /* withdraw FIAT */
                    if ($show)
                        $fiat = gmp_sub($fiat, $amount);

                    $total_fiat_withdrawal = gmp_add($total_fiat_withdrawal, $amount);

                    if ($new && $show) {
                        $period_fiat_withdrawal = gmp_add($period_fiat_withdrawal, $amount);

                        $title = '';
                        if ($voucher) {
                            $title = sprintf(_("to %svoucher") . " &quot;%s&quot;",
                                             $final ? "" : (_("unredeemed") . " "),
                                             $voucher);
                        } else
                            $title = sprintf(_("to account %s at %s"), $row['acc_num'], $row['bank']);

                        active_table_cell_for_request(sprintf("<strong title='%s'>%s%s %s %s%s</strong>",
                                                              $title,
                                                              $final ? "" : "* ",
                                                              $voucher ? _("Create voucher") . ":" : _("Withdraw"),
                                                              internal_to_numstr($amount, FIAT_PRECISION),
                                                              CURRENCY,
                                                              $final ? "" : " *"),
                                                      $reqid);
                        if ($show_prices)
                            printf("<td></td>");
                        if ($show_increments)
                            printf("<td></td>");
                        printf("<td></td>");
                        if ($show_increments)
                            printf("<td class='right'>-%s</td>", internal_to_numstr($amount, FIAT_PRECISION));
                        printf("<td class='right'>%s</td>", internal_to_numstr($fiat, FIAT_PRECISION));
                    }
                }
            }

            if ($new)
                echo "</tr>\n";
        }
    }

    if ($first && $from_zero)
        $fiat = $btc = numstr_to_internal(0);

    show_balances_in_statement($first ? _("There are no entries for this period") : _("Closing Balances"),
                               $btc, $fiat, $all_users, $show_prices, $show_increments);

    echo "</table>\n";

    if (!$all_final) {
        echo "<p>" . _("Items marked with '*' are not yet final.") . "</p>\n";
        echo "<p>" . _("Any such withdrawals and vouchers can be cancelled.") . "</p>\n";
        echo "<p>" . _("Any such deposits are pending, and should be finalised within a minute or two.") . "</p>\n";
    }

    echo "</div>";

    if (gmp_cmp($total_fiat_deposit,    $period_fiat_deposit   ) != 0 ||
        gmp_cmp($total_fiat_withdrawal, $period_fiat_withdrawal) != 0 ||
        gmp_cmp($total_btc_deposit,     $period_btc_deposit    ) != 0 ||
        gmp_cmp($total_btc_withdrawal,  $period_btc_withdrawal ) != 0 ||
        gmp_cmp($total_fiat_got,        $period_fiat_got       ) != 0 ||
        gmp_cmp($total_fiat_given,      $period_fiat_given     ) != 0 ||
        gmp_cmp($total_btc_got,         $period_btc_got        ) != 0 ||
        gmp_cmp($total_btc_given,       $period_btc_given      ) != 0)
        show_statement_summary(_("Summary of displayed entries"),
                               $period_fiat_deposit, $period_fiat_withdrawal, $period_btc_deposit, $period_btc_withdrawal,
                               $period_fiat_got, $period_fiat_given, $period_btc_got, $period_btc_given);

    show_statement_summary(_("Account Summary"),
                           $total_fiat_deposit, $total_fiat_withdrawal, $total_btc_deposit, $total_btc_withdrawal,
                           $total_fiat_got, $total_fiat_given, $total_btc_got, $total_btc_given);
}

$interval = isset($_GET['interval']) ? get('interval') : DEFAULT_STATEMENT_PERIOD;
if (isset($_GET['form'])) {
    $from_zero = isset($_GET['fromz']);
    $deposit_btc = isset($_GET['dbtc']);
    $withdraw_btc = isset($_GET['wbtc']);
    $deposit_fiat = isset($_GET['dfiat']);
    $withdraw_fiat = isset($_GET['wfiat']);
    $buy = isset($_GET['bbtc']);
    $sell = isset($_GET['sbtc']);
} else {
    $from_zero = false;
    $deposit_btc = $withdraw_btc = $deposit_fiat = $withdraw_fiat = $buy = $sell = true;
}

$user = '';
if ($is_admin) {
    if (isset($_GET['uid']) && $_GET['uid'] != '') {
        $user = get('uid', ':/?=_#~-');
        if (strlen($user) > 6)
            $user = get_uid_for_openid($user);
    } else if (isset($_GET['user']))
        $user = get('user');
}

show_statement($user,
               $interval,
               $from_zero,
               $deposit_btc, $withdraw_btc, $deposit_fiat, $withdraw_fiat, $buy, $sell);

?>
