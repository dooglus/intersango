<?php
$abspath = '/home7/worldbit/programs/intersango';
require "$abspath/errors.php";
enable_errors();

// how many confirmations we need on incoming bitcoin transfers before adding them to the user accounts
function confirmations_for_deposit() { return 4; }

// which timezone should we use
function timezone() { return "Australia/Queensland"; }

// percentage commission to charge.
function commission_percentage() { return 0.65; }

// commission cap, in AUD
function commission_cap_in_aud() { return 0.25; }

// commission cap, in BTC
function commission_cap_in_btc() { return 0.025; }

// the smallest you can say you 'have' when placing an order
function minimum_have_amount() { return '0.0005'; }

// the smallest you can say you 'want' when placing an order
function minimum_want_amount() { return '0.0005'; }

// the smallest you can withdraw (it's the same for AUD and BTC)
function minimum_withdraw() { return '0.5'; }

?>
