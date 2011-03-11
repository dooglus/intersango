        <div class='content_box'>
        <div class='content_sideshadow'>
            <h3>Currency converter</h3>
        <form id='buy_form' action='?page=place_order' method='post'>
            <table id='exchanger'>
            <tr><td>

            <p><b>Currency I have:</b></p>
            <div class='currbox_wrapper'>
                <div id='incurrency' class='currbox' onclick='javascript:rolldown_in();'>
                    <img class='currflag' src='images/gbp_flag.png' />
                    <span class='currname'>British Pound</span>
                    <div class='currbox_right'>
                        <b class='currcode'>GBP</b>
                        <img src='images/arrow_down.png' />

                    </div>
                </div>
                <div id='currsel_in'>
                    <div class='currsel_entry' onclick='javascript:select_currency_in(this, true);'>
                        <img class='currflag' src='images/gbp_flag.png' />
                        <span class='currname'>British Pound</span>
                        <div class='currbox_right'>
                            <b class='currcode'>GBP</b>
                        </div>
                    </div>

                    <div class='currsel_entry' onclick='javascript:select_currency_in(this, true);'>
                        <img class='currflag' src='images/btc_flag.png' />
                        <span class='currname'>Bitcoin</span>
                        <div class='currbox_right'>
                            <b class='currcode'>BTC</b>
                        </div>
                    </div>
                </div>
            </div>


        </td>
        <td>

            <p><b>Currency I want:</b></p>
            <div class='currbox_wrapper'>
                <div id='outcurrency' class='currbox' onclick='javascript:rolldown_out();'>
                    <img class='currflag' src='images/btc_flag.png' />
                    <span class='currname'>Bitcoin</span>

                    <div class='currbox_right'>
                        <b class='currcode'>BTC</b>
                        <img src='images/arrow_down.png' />
                    </div>
                </div>
                <div id='currsel_out'>
                    <div class='currsel_entry' onclick='javascript:select_currency_out(this, true);'>
                        <img class='currflag' src='images/gbp_flag.png' />
                        <span class='currname'>British Pound</span>
                        <div class='currbox_right'>

                            <b class='currcode'>GBP</b>
                        </div>
                    </div>
                    <div class='currsel_entry' onclick='javascript:select_currency_out(this, true);'>
                        <img class='currflag' src='images/btc_flag.png' />
                        <span class='currname'>Bitcoin</span>
                        <div class='currbox_right'>
                            <b class='currcode'>BTC</b>

                        </div>
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

        </tr>
        <tr><td></td><td>
                    <input type='hidden' name='type' value='' />
                    <input type='hidden' name='want_type' value='' />
                    <input type='submit' onclick='return buy_clicked();' value='Buy' />
        </td></tr>
        </table>
        </form>

            <p>
            Use the above to give you an indication of the current exchange rates.
            </p>
            <p>
            Select the currency you wish to buy on the left, then click Buy.
            </p>
            <p>
            Click the image below to see a graph of Bitcoin prices.
            </p>
            <p>
            <a class='image_right' target='_new' href='images/test/all_time.png'><img src='images/test/all_time_thumb.png' /></a>
            </p>

        </div>
        </div>

        <div class='content_box'>
        <div class='content_sideshadow'>
            <h3>Currency converter</h3>
            <p>
            hello
            </p>
            <p>
            If one of the values above are missing, e.g. "list-style:circle inside;", the default value for the missing property will be inserted, if any.
            </p>
            <p>
            If one of the values above are missing, e.g. "list-style:circle inside;", the default value for the missing property will be inserted, if any.
            </p>
        </div>
        </div>

