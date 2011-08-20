<?php
define('ABSPATH', '/home/worldbit/programs/intersango');
define('URLROOT', '/');

if (!file_exists(ABSPATH . "/trade.php")) {
    echo "ABSPATH should point to the intersango directory - the one that contains trade.php, etc.\n";
    exit();
}

require_once ABSPATH . "/errors.php";
enable_errors();

// path to a directory to use for per-user locks - must be writable by the user who runs PHP scripts
define('LOCK_DIR', ABSPATH . "/locks");

// .------------------------------------------------------------------------
// |  local time
// `------------------------------------------------------------------------

// which timezone should we use - something that exists in /usr/share/zoneinfo/ ?
define('TIMEZONE', "Australia/Queensland");

// how many minutes after midnight does the day start, for the purposes of limiting daily transfers
define('DAY_STARTS_MINUTES_AFTER_MIDNIGHT', 9*60 + 0); // day starts at 9:00am

// .------------------------------------------------------------------------
// |  free money! (set to zero before going live!!)
// `------------------------------------------------------------------------

// how much free AUD to give new accounts on signup
define('FREE_AUD_ON_SIGNUP', "0");

// how much free BTC to give new accounts on signup
define('FREE_BTC_ON_SIGNUP', "0");

// .------------------------------------------------------------------------
// |  security
// `------------------------------------------------------------------------

// how many minutes can a user be idle for before they're automatically logged out
define('MAX_IDLE_MINUTES_BEFORE_LOGOUT', 60);

// how often should we change the session id (in minutes)
define('MAX_SESSION_ID_LIFETIME', 10);

// how many confirmations we need on incoming Bitcoin transfers before adding them to the user accounts
define('CONFIRMATIONS_FOR_DEPOSIT', 4);

// .------------------------------------------------------------------------
// |  commission
// `------------------------------------------------------------------------

// percentage commission to charge on each AUD received; 0 for no commission
define('COMMISSION_PERCENTAGE_FOR_AUD', '0.6');

// percentage commission to charge on each BTC received; 0 for no commission
define('COMMISSION_PERCENTAGE_FOR_BTC', '0.1');

// commission cap, in AUD, when buying AUD; '0' for no cap
define('COMMISSION_CAP_IN_AUD', '0.25');

// commission cap, in BTC, when buying BTC; '0' for no cap
define('COMMISSION_CAP_IN_BTC', '0.025');

// .------------------------------------------------------------------------
// |  lower limits
// `------------------------------------------------------------------------

// the smallest you can say you 'have' when placing an order
define('MINIMUM_HAVE_AMOUNT', '0.0005');

// the smallest you can say you 'want' when placing an order
define('MINIMUM_WANT_AMOUNT', '0.0005');

// the smallest you can withdraw (it's the same for AUD and BTC)
define('MINIMUM_WITHDRAW', '0.5');

// how many decimal places allowed in BTC withdrawal (0 through 8)
//   0 means only whole bitcoins can be withdrawn;
//   8 means 1.23456789 is a valid amount to withdraw
define('BTC_WITHDRAW_DECIMAL_PLACES', '8');

// .------------------------------------------------------------------------
// |  upper limits
// `------------------------------------------------------------------------

// the total amount of BTC each user can withdraw per day
define('MAXIMUM_DAILY_BTC_WITHDRAW', '100');

// the total amount of AUD each user can transfer (in + out) per day
define('MAXIMUM_DAILY_AUD_TRANSFER', '500');

// the maximum amount of AUD each user can hold at once
define('MAXIMUM_AUD_BALANCE', '5000');

?>
