<?php

// 'key' and 'secret' are defined here
require_once "wbx_config.php";

// Authentication is performed by signing each request using
// HMAC-SHA512. The request must contain an extra value "nonce" which
// must be an always incrementing numeric value. A reference
// implementation is provided here:

class WBX_API
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
                         'Rest-Sign: '. base64_encode(hash_hmac('sha512', $post_data, $this->secret, true)));
 
        // our curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; WBX PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
        }
        curl_setopt($ch, CURLOPT_URL, SITE_URL . 'api/' . $path);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 
        // run the query
        $res = curl_exec($ch);
        if ($res === false) throw new Exception('Could not get reply: ' . curl_error($ch));

        $dec = json_decode($res, true);
        if (!$dec) throw new Exception('Invalid data received, please make sure connection is working and requested API exists');
        return $dec;
    }

    ////////////////////////////////////////////////////////////////////////
    // * withdraw_voucher.php
    // 
    // withdraw BTC or fiat to a voucher
    // 
    // in:
    //     currency: BTC or AUD
    //     amount: decimal amount to withdraw
    // 
    // out:
    //     status:  "OK" if successful
    //     voucher: voucher string
    //     reqid:   withdrawal request ID
    ////////////////////////////////////////////////////////////////////////
    function withdraw_voucher($amount, $currency)
    {
        return self::query('withdraw_voucher.php',
                           array('currency' => $currency,
                                 'amount' => $amount));
    }

    function withdraw_btc_voucher($amount)
    {
        return self::withdraw_voucher($amount, 'BTC');
    }

    function withdraw_fiat_voucher($amount)
    {
        return self::withdraw_voucher($amount, CURRENCY);
    }
}

?>
