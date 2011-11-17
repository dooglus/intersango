<?php

// get these from https://www.worldbitcoinexchange.com/?page=api
define('API_KEY'   , 'xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx');
define('API_SECRET', 'xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx');

function test()
{
    $wbx = new WBX_API(API_KEY, API_SECRET);

    var_dump($wbx->get_deposit_address());
}

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
        $this->url = 'https://www.worldbitcoinexchange.com/api';
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
        curl_setopt($ch, CURLOPT_URL, "{$this->url}/$path");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // run the query
        $res = curl_exec($ch);
        if ($res === false) throw new Exception('Could not get reply: ' . curl_error($ch));

        $dec = json_decode($res, true);
        if (!$dec) throw new Exception("Invalid data received, please make sure connection is working and requested API exists.\nresult: '$res'\n");

        return $this->last = $dec;
    }

    function ok()
    {
        return (isset($this->last['status']) &&
                $this->last['status'] == 'OK');
    }

    ////////////////////////////////////////////////////////////////////////
    // * add_order.php
    //
    // add an order to the orderbook
    //
    // needs permission: trade
    //
    // in:
    //     have_amount:   decimal amount to offer
    //     have_currency: BTC or AUD to offer
    //     want_amount:   decimal amount to request
    //     want_currency: BTC or AUD to request
    //
    // out:
    //     status:        "OK" if successful
    //     orderid:       order ID
    ////////////////////////////////////////////////////////////////////////
    function add_order($have_amount, $have_currency,
                       $want_amount, $want_currency)
    {
        return self::query('addOrder.php',
                           array('have_amount'   => $have_amount,
                                 'have_currency' => $have_currency,
                                 'want_amount'   => $want_amount,
                                 'want_currency' => $want_currency));
    }

    ////////////////////////////////////////////////////////////////////////
    // * cancel_order.php
    //
    // add an order to the orderbook
    //
    // needs permission: trade
    //
    // in:
    //     orderid:       order ID
    //
    // out:
    //     status:        "OK" if successful
    ////////////////////////////////////////////////////////////////////////
    function cancel_order($orderid)
    {
        return self::query('cancelOrder.php',
                           array('orderid'       => $orderid));
    }

    function cancel_all_orders()
    {
        $orders = self::get_orders();

        if (!self::ok())
            return $orders;

        $ret = array();
        foreach ($orders['orders'] as $order)
            array_push($ret, self::cancel_order($order['orderid']));

        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////
    // * get_deposit_address.php
    //
    // get a Bitcoin address that can be used to deposit to your account
    //
    // needs permission: read
    //
    // in: (nothing)
    //
    // out:
    //     status:  "OK" if successful
    //     address: an address you can use to send BTC to your exchange account
    ////////////////////////////////////////////////////////////////////////
    function get_deposit_address()
    {
        return self::query('getDepositAddress.php');
    }

    ////////////////////////////////////////////////////////////////////////
    // * get_orders.php
    //
    // get a list of open orders in the orderbook; for partially
    // matched orders, this reports only the remaining part of each
    //
    // needs permission: read
    //
    // in: (nothing)
    //
    // out:
    //     status:  "OK" if successful
    //     list of:
    //         orderid:       order ID
    //         text:          a plain text description of the order
    //         have_amount:   the offered amount, as a decimal
    //         have_currency: the offered currency
    //         want_amount:   the requested amount, as a decimal
    //         want_currency: the requested currency
    ////////////////////////////////////////////////////////////////////////
    function get_orders()
    {
        return self::query('getOrders.php');
    }

    ////////////////////////////////////////////////////////////////////////
    // * info.php
    //
    // get user information
    //
    // needs permission: read
    //
    // in: (nothing)
    //
    // out:
    //     status:  "OK" if successful
    //     uid      user id
    //     BTC:     Bitcoin balance
    //     AUD:     fiat balance
    ////////////////////////////////////////////////////////////////////////
    function info()
    {
        return self::query('info.php');
    }

    ////////////////////////////////////////////////////////////////////////
    // * redeem_voucher.php
    //
    // redeem BTC or fiat voucher
    //
    // needs permission: deposit
    //
    // in:
    //     voucher: voucher string
    //
    // out:
    //     status:  "OK" if successful
    //     currency: BTC or AUD
    //     amount:   decimal amount credited to account
    ////////////////////////////////////////////////////////////////////////
    function redeem_voucher($voucher)
    {
        return self::query('redeemVoucher.php',
                           array('voucher' => $voucher));
    }

    ////////////////////////////////////////////////////////////////////////
    // * withdraw_bitcoin.php
    //
    // withdraw BTC to a Bitcoin address
    //
    // needs permission: withdraw
    //
    // in:
    //     amount:         decimal amount to withdraw
    //     address:        Bitcoin address to withdraw to
    //
    // out:
    //     status:  "OK" if successful
    //     reqid:   withdrawal request ID
    ////////////////////////////////////////////////////////////////////////
    function withdraw_bitcoin($amount, $address)
    {
        return self::query('withdrawBitcoin.php',
                           array('amount'         => $amount,
                                 'address'        => $address));
    }

    ////////////////////////////////////////////////////////////////////////
    // * withdraw_fiat.php
    //
    // withdraw fiat to a bank account
    //
    // needs permission: withdraw
    //
    // in:
    //     amount:         decimal amount to withdraw
    //     name_holder:    name of account holder
    //     name_bank:      name of the bank
    //     account_number: account number
    //     sort_code:      bank branch identifier (BSB, sort code, etc.)
    //     ref:            your reference (optional)
    //
    // out:
    //     status:  "OK" if successful
    //     reqid:   withdrawal request ID
    ////////////////////////////////////////////////////////////////////////
    function withdraw_fiat($amount, $name_holder, $name_bank, $account_number, $sort_code, $ref = '')
    {
        return self::query('withdrawFiat.php',
                           array('amount'         => $amount,
                                 'name_holder'    => $name_holder,
                                 'name_bank'      => $name_bank,
                                 'account_number' => $account_number,
                                 'sort_code'      => $sort_code,
                                 'ref'            => $ref));
    }

    ////////////////////////////////////////////////////////////////////////
    // * withdraw_voucher.php
    //
    // withdraw BTC or fiat to a voucher
    //
    // needs permission: withdraw
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
        return self::query('withdrawVoucher.php',
                           array('currency' => $currency,
                                 'amount' => $amount));
    }

    function withdraw_btc_voucher($amount)
    {
        return self::withdraw_voucher($amount, 'BTC');
    }

    function withdraw_fiat_voucher($amount)
    {
        return self::withdraw_voucher($amount, 'AUD');
    }
}

try {
    test();
} catch (Exception $e) {
    echo "Error: {$e->getMessage()}";
}

?>
