<?php

function show_mini_orderbook_table($bids)
{
    global $buy, $sell;

    echo "<table class='display_data'>";
    echo "<tr><th colspan=3 style='text-align: center;'>" .
        ($bids
         ? sprintf(_("People Selling BTC for %s"), CURRENCY)
         : sprintf(_("People Buying BTC for %s" ), CURRENCY)) .
        "</th></tr>" .
        "<tr><th valign='bottom' class='right'>" .
        sprintf(_("Price<br/>(%s per BTC)"), CURRENCY) . "</th>" .
        "<th valign='bottom' class='right'>" . _("Depth<br/>(in BTC)") . "</th>" .
        "<th valign='bottom' class='right'>" . _("Cumulative<br/>Depth<br/>(in BTC)") . "</th>" .
        "</tr>";

    $limit = '';
    if ($bids) {
        $price = "amount/want_amount";
        $amount = "want_amount";
        $type = CURRENCY;
        $order = "DESC";
        if ($buy) $limit = "AND $price > " . $buy * (1 - ORDERBOOK_PRICE_RANGE_PERCENTAGE/100);
    } else {
        $price = "want_amount/amount";
        $amount = "amount";
        $type = "BTC";
        $order = "ASC";
        if ($sell) $limit = "AND $price < " . $sell * (1 + ORDERBOOK_PRICE_RANGE_PERCENTAGE/100);
    }

    $result = do_query("
        SELECT
            $amount as btc_amount,
            amount,
            want_amount
        FROM
            orderbook
        WHERE
            type = '$type' AND
            status='OPEN'
            $limit
        ORDER BY
            $price $order, timest ASC
    ");

    $last_price = 0;
    $btc_amount_at_price = "0";
    $total_btc_amount = "0";
    while ($row = mysql_fetch_array($result)) {
        $btc_amount = $row['btc_amount'];
        if ($bids)
            $price = bcdiv($row['amount'], $row['want_amount'], PRICE_PRECISION);
        else
            $price = bcdiv($row['want_amount'], $row['amount'], PRICE_PRECISION);
        if ($price == $last_price)
            $btc_amount_at_price = gmp_add($btc_amount_at_price, $btc_amount);
        else {
            if ($last_price)
                echo "<tr>" .
                    "<td class='right'>$last_price</td>" .
                    "<td class='right'>" . internal_to_numstr($btc_amount_at_price, BTC_PRECISION) . "</td>" .
                    "<td class='right'>" . internal_to_numstr($total_btc_amount, BTC_PRECISION) . "</td>" .
                    "</tr>\n";
            $last_price = $price;
            $btc_amount_at_price = $btc_amount;
        }
        $total_btc_amount = gmp_add($total_btc_amount, $btc_amount);
    }
    if ($last_price)
        echo "<tr>" .
            "<td class='right'>$last_price</td>" .
            "<td class='right'>" . internal_to_numstr($btc_amount_at_price, BTC_PRECISION) . "</td>" .
            "<td class='right'>" . internal_to_numstr($total_btc_amount, BTC_PRECISION) . "</td>" .
            "</tr>\n";

    echo "</table>\n";
}

function show_mini_orderbook()
{
    echo "<table><tr><td>\n";
    show_mini_orderbook_table(true);
    echo "</td><td>";
    show_mini_orderbook_table(false);
    echo "</td></tr></table>";
    echo "<p>" . sprintf(_("Showing all orders within %s%% of the best price."), ORDERBOOK_PRICE_RANGE_PERCENTAGE) . "</p>\n";
}

    if (isset($_GET['have']))
        $in_amount = internal_to_numstr(get('have'));
    else
        $in_amount = '';

    if (isset($_GET['want']))
        $out_amount = internal_to_numstr(get('want'));
    else
        $out_amount = '';

    if (isset($_GET['rate']))
        $rate = get('rate');
    else
        $rate = '';
?>
        <div class='content_box'>
            <h3><?php echo _("Currency converter"); ?></h3>
        <p><b><?php printf(_("World Bitcoin Exchange allows you to trade %s for Bitcoins (BTC) or Bitcoins for %s with other World Bitcoin Exchange users."),
                           CURRENCY_FULL_PLURAL . " (" . CURRENCY . ")",
                           CURRENCY_FULL_PLURAL); ?></a></b></p>
        <form id='buy_form' action='?page=place_order' method='post'>
            <table id='exchanger'>
            <tr><td>

            <p><b><?php echo _("Currency I have"); ?>:</b></p>
            <div class='currbox_wrapper'>
                <div id='incurrency' class='currbox' onclick='javascript:rolldown_in();'>
                    <div class='currbox_right'>
                        <b class='currcode'><?php echo CURRENCY; ?></b>
                        <img src='images/arrow_down.png' />
                    </div>

                    <img class='currflag' src='images/<?php echo strtolower(CURRENCY) ?>_flag.png' />
                    <span class='currname'><?php echo CURRENCY_FULL; ?></span>
                </div>
                <div id='currsel_in'>
                    <div class='currsel_entry' onclick='javascript:select_currency_in(this, true);'>
                        <div class='currbox_right'>
                            <b class='currcode'><?php echo CURRENCY; ?></b>
                        </div>
                        <img class='currflag' src='images/<?php echo strtolower(CURRENCY) ?>_flag.png' />
                        <span class='currname'><?php echo CURRENCY_FULL; ?></span>
                    </div>
                    <div class='currsel_entry' onclick='javascript:select_currency_in(this, true);'>
                        <div class='currbox_right'>
                            <b class='currcode'>BTC</b>
                        </div>
                        <img class='currflag' src='images/btc_flag.png' />
                        <span class='currname'>Bitcoin</span>
                    </div>
                </div>
            </div>
        </td>
        <td>
            <p><b><?php echo _("Currency I want"); ?>:</b></p>
            <div class='currbox_wrapper'>
                <div id='outcurrency' class='currbox' onclick='javascript:rolldown_out();'>
                    <div class='currbox_right'>
                        <b class='currcode'>btc</b>
                        <img src='images/arrow_down.png' />
                    </div>

                    <img class='currflag' src='images/btc_flag.png' />
                    <span class='currname'>Bitcoin</span>
                </div>
                <div id='currsel_out'>
                    <div class='currsel_entry' onclick='javascript:select_currency_out(this, true);'>
                        <div class='currbox_right'>
                            <b class='currcode'><?php echo CURRENCY; ?></b>
                        </div>
                        <img class='currflag' src='images/<?php echo strtolower(CURRENCY) ?>_flag.png' />
                        <span class='currname'><?php echo CURRENCY_FULL; ?></span>
                    </div>
                    <div class='currsel_entry' onclick='javascript:select_currency_out(this, true);'>
                        <div class='currbox_right'>
                            <b class='currcode'>BTC</b>
                        </div>
                        <img class='currflag' src='images/btc_flag.png' />
                        <span class='currname'>Bitcoin</span>
                    </div>
                </div>
            </div>
        </td>
        </tr>

            <tr>
            <td>
            <input id='inamount' autocomplete='off' name='amount' class='curramount' type="text" size="20" value="<?php echo $in_amount?>" onkeyup='typed_amount_in(event);'>
            </td>

            <td>
            <input id='outamount' autocomplete='off' name='want_amount' class='curramount' type="text" size="20" value="<?php echo $out_amount?>" onkeyup='typed_amount_out(event);'>
            </td>
            </tr>
        <tr><td>
            <b>Price:</b>
            <input id='price' autocomplete='off' name='price' class='price' type="text" size="20" value="<?php echo $rate?>" onkeyup='typed_amount_price(event);'>
        </td><td>
    <?php
    if ($is_logged_in) { ?>
                    <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
                    <input type='hidden' name='type' value='' />
                    <input type='hidden' name='want_type' value='' />
                    <input type='submit' onclick='return buy_clicked();' value='Buy' />
    <?php } ?>
        </td></tr>
        </table>
        </form>

    <?php
    if ($is_logged_in) { ?>
            <p>
            <?php printf(_("You have %s."), balances_text($is_logged_in)); ?>
            </p>
    <?php }
    else { ?>
            <p><?php echo _("To begin trading you will need an OpenID account."); ?></p>
            <p><?php echo _("If you do not have an OpenID login then we recommend"); ?> <a href="https://www.myopenid.com/">MyOpenID</a></p>
            <p><?php echo _("This is a Two-Factor Authentication Security Supported Exchange, for more Info see our help section."); ?></p>.
    <?php }

    show_mini_orderbook();
?>
        </div>

        <div class='content_box'>
            <h3><?php echo _("Bitcoin"); ?></h3>
            <p>
            <?php printf(_("%sBitcoin%s is an emerging crypto-currency that offers many exciting possibilities. See %sBitcoin Consultancy%s for Bitcoin related projects and questions."),
                         '<a target="_blank" href="http://bitcoin.org">',
                         '</a>',
                         '<a target="_blank" href="http://bitcoinconsultancy.com/">',
                         '</a>'); ?>
            </p>
        </div>

        <div class='content_box'>
            <h3><?php echo _("Commission"); ?></h3>
<?php
    if (COMMISSION_PERCENTAGE_FOR_FIAT == 0 && COMMISSION_PERCENTAGE_FOR_BTC == 0)
        echo "<p>" . _("All trades are free of commission") . "</p>\n";
    else {
        echo "<p>" . _("Commission is charged at the following rates") . ":</p>\n";
        show_commission_rates();
    }
?>
        </div>

<div class='content_box'>
<?php show_contact_info(); ?>
        </div>
