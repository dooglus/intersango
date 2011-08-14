<?php
    if (isset($_GET['have']))
        $in_amount = internal_to_numstr(get('have'));
    else
        $in_amount = '';

    if (isset($_GET['want']))
        $out_amount = internal_to_numstr(get('want'));
    else
        $out_amount = '';
?>
        <div class='content_box'>
            <h3>Currency converter</h3>
            <p><b>Intersango is an exchange. Here we allow you to trade Australian Dollars (AUD) for Bitcoins (BTC) or Bitcoins for Australian Dollars with other Intersango users.</a></b></p>
            <p><b>To <a target="_blank" href='https://britcoin.co.uk/'>buy bitcoins</a> with GBP, visit <a target="_blank" href='https://britcoin.co.uk/'>britcoin.co.uk</a></b></p> 
            <p><b>To <a target="_blank" href='https://intersango.com/'>buy bitcoins</a> with EUR, visit the newly opened site <a target="_blank" href='https://intersango.com/'>intersango.com</a></b></p>
            <p><b>To <a target="_blank" href='https://intersango.us/'>buy bitcoins</a> with USD, visit the newly opened site <a target="_blank" href='https://intersango.us/'>intersango.us</a></b></p>
        <form id='buy_form' action='?page=place_order' method='post'>
            <table id='exchanger'>
            <tr><td>

            <p><b>Currency I have:</b></p>
            <div class='currbox_wrapper'>
                <div id='incurrency' class='currbox' onclick='javascript:rolldown_in();'>
                    <div class='currbox_right'>
                        <b class='currcode'>aud</b>
                        <img src='images/arrow_down.png' />
                    </div>

                    <img class='currflag' src='images/aud_flag.png' />
                    <span class='currname'>Australian Dollar</span>
                </div>
                <div id='currsel_in'>
                    <div class='currsel_entry' onclick='javascript:select_currency_in(this, true);'>
                        <div class='currbox_right'>
                            <b class='currcode'>AUD</b>
                        </div>
                        <img class='currflag' src='images/aud_flag.png' />
                        <span class='currname'>Australian Dollar</span>
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
            <p><b>Currency I want:</b></p>
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
                            <b class='currcode'>AUD</b>
                        </div>
                        <img class='currflag' src='images/aud_flag.png' />
                        <span class='currname'>Australian Dollar</span>
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
            <input id='inamount' name='amount' class='curramount' type="text" size="20" value="<?php echo $in_amount?>" onkeyup='typed_amount_in();'>
            </td>

            <td>
            <input id='outamount' name='want_amount' class='curramount' type="text" size="20" value="<?php echo $out_amount?>" onkeyup='typed_amount_out();'>
            </td>
            </tr>
    <?php
    if ($is_logged_in) { ?>
        <tr><td></td><td>
                    <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
                    <input type='hidden' name='type' value='' />
                    <input type='hidden' name='want_type' value='' />
                    <input type='submit' onclick='return buy_clicked();' value='Buy' />
        </td></tr>
    <?php } ?>
        </table>
        </form>

    <?php
    if ($is_logged_in) { ?>
            <p>
            Use the above to give you an indication of the current exchange rates.
            </p>
        <?php show_balances($is_logged_in, $indent=true); ?>
            <p>
            Select the currency you wish to buy on the left, then click Buy.
            </p>
    <?php }
    else { ?>
            <p>To begin trading you will need an OpenID account.</p>
            <p>If you do not have an OpenID login then we recommend <a href="https://www.myopenid.com/">MyOpenID</a></p>
            <p>This is a Two-Factor Authentication Security Supported Exchange, for more Info see our help section.</p>.
    <?php } ?>
        </div>

        <div class='content_box'>
            <h3>Bitcoin</h3>
            <p>
            <a target="_blank" href='http://bitcoin.org'>Bitcoin</a> is an emerging crypto-currency that offers many exciting possibilities. See <a target="_blank" href='http://bitcoinconsultancy.com/'>Bitcoin Consultancy</a> for bitcoin related projects and questions.
            </p>
        </div>

        <div class='content_box'>
            <h3>Commission</h3>
<?php
    if (COMMISSION_PERCENTAGE_FOR_AUD == 0 && COMMISSION_PERCENTAGE_FOR_BTC == 0)
        echo "<p>All trades are free of commission</p>\n";
    else {
        echo "<p>Commission is charged at the following rates:</p>\n";
        show_commission_rates();
    }
?>
        </div>

<div class='content_box'>
<?php show_contact_info(); ?>
        </div>
