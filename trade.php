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
                        <b class='currcode'>AUD</b>
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
                        <b class='currcode'>BTC</b>
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
            <input id='inamount' name='amount' class='curramount' type="text" size="20" value="" onkeyup='typed_amount_in();'>
            </td>

            <td>
            <input id='outamount' name='want_amount' class='curramount' type="text" size="20" value="" onkeyup='typed_amount_out();'>
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
        <?php show_balances($indent=true); ?>
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
    if (commission_percentage_for_aud() == 0 && commission_percentage_for_btc() == 0)
        echo "<p>All trades are free of commission</p>\n";
    else {
        echo "<p>Commission is charged at the following rates:</p>\n";
        show_commission_rates();
    }
?>
        </div>

<div class='content_box'>
<h3>Contact info</h3>
<p>support@Intersango.com.au</p>
<p>Call +617 3102-9666</p>
<p>Office Hours Mon-Fri 9am to 5pm</p> 
<p>(Standard time zone: UTC/GMT +10 hours - it is currently <?php require_once "util.php"; echo get_time_text(); ?>)</p>
<p>
<b>High Net Worth Property Pty Ltd <br /></b>
Trading As: World Bitcoin Exchange <br />
ACN: 61 131 700 779 <br />
Gold Coast <br />
Queensland <br />
Australia <br />
4208
</p>
        </div>
