<?php
$abspath = '/home7/worldbit/programs/intersango';
$urlroot = '/';
require_once "$abspath/errors.php";
enable_errors();

// path to a directory to use for per-user locks - must be writable by the user who runs PHP scripts
function lock_dir() { return "/home/chris/Programs/intersango/programs/intersango/locks/"; }

// how many minutes can a user be idle for before they're automatically logged out
function max_idle_minutes_before_logout() { return 60; }

// how often should we change the session id (in minutes)
function max_session_id_lifetime() { return 10; }

// how many confirmations we need on incoming bitcoin transfers before adding them to the user accounts
function confirmations_for_deposit() { return 4; }

// which timezone should we use
function timezone() { return "Australia/Queensland"; }

// percentage commission to charge on each AUD received; 0 for no commission
function commission_percentage_for_aud() { return '0.6'; }

// percentage commission to charge on each BTC received; 0 for no commission
function commission_percentage_for_btc() { return '0.1'; }

// commission cap, in AUD, when buying AUD; '0' for no cap
function commission_cap_in_aud() { return '0.25'; }

// commission cap, in BTC, when buying BTC; '0' for no cap
function commission_cap_in_btc() { return '0.025'; }

// the smallest you can say you 'have' when placing an order
function minimum_have_amount() { return '0.0005'; }

// the smallest you can say you 'want' when placing an order
function minimum_want_amount() { return '0.0005'; }

// the smallest you can withdraw (it's the same for AUD and BTC)
function minimum_withdraw() { return '0.5'; }

?>
