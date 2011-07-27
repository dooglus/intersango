        <div class='content_box'>
            <h3>Currency converter</h3>
            <p><b>To buy bitcoins with euros, visit our newly opened sister site <a href='https://intersango.com/'>Intersango.com</a></b></p>
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
    if ($loggedin) { ?>
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
    if ($loggedin) { ?>
            <p>
            Use the above to give you an indication of the current exchange rates.
            </p>
        <?php show_balances($indent=true); ?>
            <p>
            Select the currency you wish to buy on the left, then click Buy.
            </p>
    <?php }
    else { ?>
            <p>
            To begin trading you will need an OpenID account.
            </p>
            <p>If you do not have an OpenID login then we recommend <a href="https://www.myopenid.com/">MyOpenID</a>.
    <?php } ?>
        </div>

        <div class='content_box'>
            <h3>Bitcoin</h3>
            <p>
            <a href='https://bitcoinconsultancy.com/wiki/index.php/Bitcoin_introduction'>Bitcoin</a> is an emerging crypto-currency that offers many exciting possibilities. See our sister group <a href='http://bitcoinconsultancy.com/'>Bitcoin Consultancy</a> for bitcoin related projects and questions.
            </p>
        </div>

<div class='content_box'>
<h3>Contact info</h3>
<p>support@britcoin.co.uk</p>
<p>
<b>Intersango Ltd <br /></b>
3rd Floor 14 Hanover Street <br />
London <br />
England <br />
W1S 1YH
</p>
        </div>

