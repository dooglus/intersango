<?php

require_once "mtgox_config.php";

// In order to convert the int to a decimal you can...
// kind of field 	...divide by 	...multiply by
// BTC (volume, amount) 	1E8 (10,000,000) 	0.00000001
// USD (price) 	1E5 (100,000) 	0.00001
// JPY (price) 	1E3 (1,000) 	0.001
// 
// Authentication is performed by signing each request using
// HMAC-SHA512. The request must contain an extra value "nonce" which
// must be an always incrementing numeric value. In addition to the
// "nonce" value, your POST data must also include your username and
// password values, named "name" and "pass" respectively. A reference
// implementation is provided here:

class MtGox_API
{
    function __construct($key, $secret)
    {
        $this->key = $key;
        $this->secret = $secret;
    }

    function query($path, array $req = array())
    {
        // generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);
 
        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
 
        // generate the extra headers
        $headers = array('Rest-Key: ' . $this->key,
                         'Rest-Sign: '. base64_encode(hash_hmac('sha512', $post_data, base64_decode($this->secret), true)));
 
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MtGox PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        }
        curl_setopt($ch, CURLOPT_URL, 'https://mtgox.com/api/' . $path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CAINFO, ABSPATH . "/cert/facacbc6.0");
 
        // run the query
        $res = curl_exec($ch);
        if ($res === false) throw new Exception('Could not get reply: ' . curl_error($ch));
        $dec = json_decode($res, true);
        if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
        return $dec;
    }

    function get_info()
    {
        return self::query('0/info.php');
    }
    
    ////////////////////////////////////////////////////////////////////////
    // * 0/getFunds.php
    // 
    // Get your current balance
    // 
    // https://mtgox.com/api/0/getFunds.php
    ////////////////////////////////////////////////////////////////////////

    function get_funds()
    {
        return self::query('0/getFunds.php');
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/buyBTC.php
    // 
    // Place an order to Buy BTC
    // 
    // POST data: amount=#&price=#
    // 
    // returns a list of your open orders
    ////////////////////////////////////////////////////////////////////////

    function buy_btc($amount, $price)
    {
        return self::query('0/buyBTC.php',
                           array('amount' => $amount,
                                 'price'  => $price));
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/sellBTC.php
    // 
    // Place an order to Sell BTC
    // 
    // POST data: &amount=#&price=#
    // 
    // returns a list of your open orders
    ////////////////////////////////////////////////////////////////////////

    function sell_btc($amount, $price)
    {
        return self::query('0/sellBTC.php',
                           array('amount' => $amount,
                                 'price'  => $price));
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/getOrders.php
    // 
    // Fetch a list of your open Orders
    // 
    // returned type: 1 for sell order or 2 for buy order
    // 
    // status: 1 for active, 2 for not enough funds
    ////////////////////////////////////////////////////////////////////////

    function get_orders()
    {
        return self::query('0/getOrders.php');
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/cancelOrder.php
    // 
    // Cancel an order
    // 
    // POST data: oid=#&type=#
    // 
    // oid: Order ID
    ////////////////////////////////////////////////////////////////////////

    function cancel_order($orderid)
    {
        return self::query('0/cancelOrder.php',
                           array('oid'    => $orderid));
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/redeemCode.php
    // 
    // Used to redeem a mtgox coupon code
    // 
    //     call with a post parameter "code" containing the code to redeem 
    // 
    //     it will return an array with amount (float amount value of code), currency (3 letters, BTC or USD), reference (the transaction id), and status 
    // 
    ////////////////////////////////////////////////////////////////////////

    function deposit_coupon($code)
    {
        return self::query('0/redeemCode.php',
                           array('code'   => $code));
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/withdraw.php
    // 
    // withdraw / Send BTC
    // 
    // POST data: group1=BTC&btca=bitcoin_address_to_send_to&amount=#
    // 
    //     pass btca parameter to withdraw to a btc adress 
    //     pass group1 for a coupon : BTC2CODE or USD2CODE 
    //     pass group1=DWUSD for a dwolla withdraw 
    // 
    //     return code and status if successful 
    ////////////////////////////////////////////////////////////////////////

    function withdraw_btc_coupon($amount)
    {
        return self::query('0/withdraw.php',
                           array('group1' => 'BTC2CODE',
                                 'amount' => $amount));
    }

    function withdraw_fiat_coupon($amount, $currency = CURRENCY)
    {
        return self::query('0/withdraw.php',
                           array('group1'   => 'USD2CODE',
                                 'amount'   => $amount,
                                 'Currency' => $currency)); // has to have capital 'C'; $currency = 'AUD' works
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/btcAddress.php
    // 
    // get a bitcoin deposit adress for your account
    // 
    //     returns a bitcoin deposit address 
    ////////////////////////////////////////////////////////////////////////

    function get_btc_address()
    {
        return self::query('0/btcAddress.php');
    }

    ////////////////////////////////////////////////////////////////////////
    // * 0/history_[CUR].csv
    // 
    // Allows downloading your activity history for a given currency (BTC or USD for now).
    // 
    // https://mtgox.com/api/0/history_BTC.php
    // https://mtgox.com/api/0/history_USD.php
    ////////////////////////////////////////////////////////////////////////

    function get_btc_history()
    {
        return self::query('0/history_BTC.csv'); /* doesn't work */
    }

    function get_usd_history()
    {
        return self::query('0/history_USD.csv'); /* doesn't work */
    }
}

?>
