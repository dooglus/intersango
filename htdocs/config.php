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

// if user account is already busy, whether to wait for it to finish (true) or fail with an error (false) 
define('BLOCKING_LOCKS', true);

// what locale to use for translations
define('LOCALE', 'en_CA');

// where to write logs
define('LOGFILE',          ABSPATH . '/log.txt');
define('PROBLEM_LOGFILE',  ABSPATH . '/log-problem.txt');
define('ERROR_LOGFILE',    ABSPATH . '/log-error.txt');
define('BAD_PAGE_LOGFILE', ABSPATH . '/log-bad-page.txt');

// whether to link to bitcoincharts;  '' means don't link, otherwise use a URL like 'http://bitcoincharts.com/markets/wbxAUD.html'
define('CHARTS_PAGE', '');

// the default time period to show on the statement page
define('DEFAULT_STATEMENT_PERIOD', '1 week');

// google analytics account number for visitor tracking
define('ANALYTICS_ACCOUNT', 'UA-26265923-1');


// .------------------------------------------------------------------------
// |  currency
// `------------------------------------------------------------------------

define('CURRENCY',             'AUD');
define('CURRENCY_NATIONALITY', 'Australian');
define('CURRENCY_FULL',        'Australian Dollar');
define('CURRENCY_FULL_PLURAL', 'Australian Dollars');

// .------------------------------------------------------------------------
// |  wallet thresholds
// `------------------------------------------------------------------------

// then the wallet contains less than this many bitcoins, send a warning email
define('WARN_LOW_WALLET_THRESHOLD', 150);

// then the wallet contains more than this many bitcoins, send a warning email
define('WARN_HIGH_WALLET_THRESHOLD', 350);

// .------------------------------------------------------------------------
// |  contact details
// `------------------------------------------------------------------------

// address to email if the script discovers a problem
define('TECH_EMAIL_ADDRESS',    'Chris Moore <dooglus@gmail.com>, Andre Jensen <gorkalitus@gmail.com>');

// the name of the site
define('SITE_NAME',             'World Bitcoin Exchange');

// a description
define('SITE_DESCRIPTION',      SITE_NAME . ' allows you to trade Australian Dollars (AUD) for Bitcoins (BTC) with other users.  We have very low fees and an active market.');

// an icon for the site
define('SITE_IMAGE',            'https://www.worldbitcoinexchange.com/images/logo200.jpg');

// address to send email from
define('EMAIL_FROM_ADDRESS',    SITE_NAME . ' <support@intersango.com.au>');

// details to show on the help page
define('CONTACT_EMAIL_ADDRESS', 'support@intersango.com.au');
define('CONTACT_SKYPE_ADDRESS', 'worldbitcoinexchange');
define('CONTACT_FACEBOOK_URL',  'http://www.facebook.com/pages/World-Bitcoin-Exchange/227118550652605');
define('CONTACT_FACEBOOK_NAME', 'worldbitcoinexchange');
define('CONTACT_TWITTER_NAME',  'worldbitcoinx');
define('CONTACT_PHONE_NUMBER',  '+617 3102-9666');
define('CONTACT_OFFICE_HOURS',  'Mon-Fri 9am to 5pm');
define('CONTACT_TIME_ZONE',     'UTC/GMT +10 hours');
define('CONTACT_ADDRESS_ETC',   "<b>High Net Worth Property Pty Ltd</b><br/>
                                 Trading As: World Bitcoin Exchange<br/>
                                 ACN: 61 131 700 779<br/>
                                 Gold Coast<br/>
                                 Queensland<br/>
                                 Australia<br/>
                                 4208");

// .------------------------------------------------------------------------
// |  bank account details for deposits
// `------------------------------------------------------------------------

define('DEPOSIT_BANK_ACCOUNT_TITLE',  'High Net Worth Property PTY LTD');
define('DEPOSIT_BANK_NAME',           'ANZ');
define('DEPOSIT_BANK_ACCOUNT_NUMBER', '2034-65422');
define('DEPOSIT_BANK_BRANCH_ID',      '014-506');

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

// how much free FIAT to give new accounts on signup
define('FREE_FIAT_ON_SIGNUP', "0");

// how much free BTC to give new accounts on signup
define('FREE_BTC_ON_SIGNUP', "0");

// .------------------------------------------------------------------------
// |  displayed precision
// `------------------------------------------------------------------------

// number of decimal places to show when showing fiat amounts
define('FIAT_PRECISION' , 2);

// number of decimal places to show when showing bitcoin amounts
define('BTC_PRECISION'  , 4);

// number of decimal places to show when showing prices
define('PRICE_PRECISION', 3);

// .------------------------------------------------------------------------
// |  vouchers
// `------------------------------------------------------------------------

// vouchers look like "WBX-BTC-5HZKF-PEL08-J39BK-JBEL8" - the first word is fixed to this value:
define('VOUCHER_PREFIX', 'WBX');

// which characters to use in the last 4 blocks of 5 characters
// using 33 characters, in 4 blocks of 5 gives us 100 bits of entropy
// http://xkcd.com/936/ says that's enough
define('VOUCHER_CHARS', '0123456789ABCDEFGHJKLMNPRSTUVWXYZ'); // 0-9, A-Z without I, O, or Q

// should we convert input voucher strings to all uppercase before
// checking for validity?
define('VOUCHER_FORCE_UPPERCASE', true);

// there are no I, O, or Q characters in vouchers.  If the user types
// an 'O' when redeeming a voucher, replace it with a '0'.  this is a
// comma separated list of (from,to) pairs of characters.  This is
// case sensitive, but is done after forcing the input voucher to all
// uppercase if VOUCHER_FORCE_UPPERCASE is true
define('VOUCHER_REPLACE', 'I1,O0,Q0');

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

// percentage commission to charge on each FIAT received; 0 for no commission
define('COMMISSION_PERCENTAGE_FOR_FIAT', '0.6');

// percentage commission to charge on each BTC received; 0 for no commission
define('COMMISSION_PERCENTAGE_FOR_BTC', '0.1');

// commission cap, in FIAT, when buying FIAT; '0' for no cap
define('COMMISSION_CAP_IN_FIAT', '0.25');

// commission cap, in BTC, when buying BTC; '0' for no cap
define('COMMISSION_CAP_IN_BTC', '0.025');

// percentage commission to charge on fiat deposits received via MtGox vouchers
define('COMMISSION_PERCENTAGE_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER', '3.0');

// commission cap on fiat deposits received via MtGox vouchers
define('COMMISSION_CAP_FOR_DEPOSIT_MTGOX_FIAT_VOUCHER', '5.0');

// // percentage commission to charge on fiat withdrawals made via MtGox vouchers
// define('COMMISSION_PERCENTAGE_FOR_WITHDRAW_MTGOX_FIAT_VOUCHER', '3.0');
// 
// // commission cap on fiat withdrawals made via MtGox vouchers
// define('COMMISSION_CAP_FOR_WITHDRAW_MTGOX_FIAT_VOUCHER', '5.0');

// .------------------------------------------------------------------------
// |  lower limits
// `------------------------------------------------------------------------

// the smallest you can say you 'have' when placing an order
define('MINIMUM_HAVE_AMOUNT', '0.0005');

// the smallest you can say you 'want' when placing an order
define('MINIMUM_WANT_AMOUNT', '0.0005');

// the smallest you can withdraw (it's the same for FIAT and BTC)
define('MINIMUM_WITHDRAW', '0.5');

// how many decimal places allowed in BTC withdrawal (0 through 8)
//   0 means only whole bitcoins can be withdrawn;
//   8 means 1.23456789 is a valid amount to withdraw
define('BTC_WITHDRAW_DECIMAL_PLACES', '8');

// if both sides of an order become smaller than this many (10^-8)th's of a unit then the order is closed
// because it's close enough to zero to not matter.
define('DUST_THRESHOLD', 5);

// .------------------------------------------------------------------------
// |  upper limits
// `------------------------------------------------------------------------

// the total amount of BTC each user can withdraw per day
define('MAXIMUM_DAILY_BTC_WITHDRAW', '100');

// the total amount of FIAT each user can transfer (in + out) per day
define('MAXIMUM_DAILY_FIAT_TRANSFER', '500');

// the maximum amount of FIAT each user can hold at once
define('MAXIMUM_FIAT_BALANCE', '5000');

// .------------------------------------------------------------------------
// |  orderbook
// `------------------------------------------------------------------------

// how many buy and sell orders to show in the orderbook by default
define('DEFAULT_ORDERBOOK_DEPTH', 10);

// should we show cumulative depths in the orderbook?
define('SHOW_CUMULATIVE_DEPTH', true);

// at what percentage above (or below) the best price should we cut off showing the orderbook?
define('ORDERBOOK_PRICE_RANGE_PERCENTAGE', 50);
?>
