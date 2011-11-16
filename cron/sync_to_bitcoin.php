<?php

require_once '../util.php';

$is_logged_in = 'sync_to_bitcoin';

foreach (bitcoin_list_accounts(CONFIRMATIONS_FOR_DEPOSIT) as $account => $balance) {
    if ($balance) {
        try {
            get_openid_for_user($account); // check they have an account
        } catch (Exception $e) { continue; }

        get_user_lock($account);
        addlog(LOG_CRONJOB, sprintf("add %s BTC for user %s", internal_to_numstr($balance), $account));
        sync_to_bitcoin((string)$account);
        release_lock($account);
    }
}

?>
