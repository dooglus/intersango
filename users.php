<?php

function show_verify_user_form()
{
    echo "<div class='content_box'>\n";
    echo "  <h3>" . _("Verify User") . "</h3>\n";

    handle_verify_user_request();

    echo "  <form action='' class='indent_form' method='post'>\n";
    echo "    <input type='hidden' name='verify_user' value='true' />\n";
    echo "    <label for='uid'>" . _("User ID") . "</label>\n";
    echo "    <input id='uid' type='text' name='uid' />\n";
    echo "    <input type='submit' value='Verify User' />\n";
    echo "  </form>\n";
    echo "</div>\n";
}

function handle_verify_user_request()
{
    if (isset($_POST['verify_user'])) {
        $uid = post('uid');
        try {
            $verified = get_verified_for_user($uid);
            if ($verified)
                echo "<p>User $uid was already verified.  Any more?</p>\n";
            else {
                do_query("UPDATE users SET verified = 1 WHERE uid = '$uid'");
                if (mysql_affected_rows() == 1)
                    echo "<p>Verified user $uid.  Any more?</p>\n";
                else
                    throw new Error("Unknown Error", "This shouldn't happen.  Please report it.");
            }
        } catch (Exception $e) {
            echo "<p>{$e->getMessage()}.  Try again?</p>\n";
        }
    }
}

function show_users_header()
{
    echo "<tr>";
    echo "<th></th>";
//  echo "<th></th>";
    echo "<th colspan='3' style='text-align: center;'>" . CURRENCY . "</th>";
    echo "<th colspan='3' style='text-align: center;'>BTC</th>";
    echo "</tr>\n";
    echo "<tr>";
    echo "<th>" . _("UID") . "</th>";
//  echo "<th>" . _("OID") . "</th>";
    echo "<th class='right'>" . _("On Hand") . "</th>";
    echo "<th class='right'>" . _("In Book") . "</th>";
    echo "<th class='right'>" . _("Total") . "</th>";
    echo "<th class='right'>" . _("On Hand") . "</th>";
    echo "<th class='right'>" . _("In Book") . "</th>";
    echo "<th class='right'>" . _("Total") . "</th>";
//  echo "<th>" . _("Registered") . "</th>";
    echo "</tr>\n";
}

function show_users()
{
    $omit_zero_balances = true;
    $omit_very_low_balances = true;

    echo "<div class='content_box'>\n";
    echo "<h3>" . _("Users") . "</h3>\n";

    $query = "
    SELECT
        u.uid, oidlogin, is_admin, timest, a.amount as fiat, b.amount as btc
    FROM
        users as u
    JOIN
        purses as a
    ON
        a.uid = u.uid AND a.type = '" . CURRENCY . "'
    JOIN
        purses as b
    ON
        b.uid = u.uid AND b.type = 'BTC'
    ORDER BY
        is_admin DESC, u.uid;
    ";

    $result = do_query($query);
    $fiat_total = $c_fiat_total = $t_fiat_total = '0';
    $btc_total = $c_btc_total = $t_btc_total = '0';
    $first = true;
    $count_users = $count_funded_users = $count_low_balance_users = $count_shown_users = 0;

    // omit users who don't have more than just least-significant-digit amounts of anything
    $tiny_fiat = pow(10, 8 - FIAT_PRECISION) * 10;
    $tiny_btc  = pow(10, 8 - BTC_PRECISION) * 10;

    while ($row = mysql_fetch_assoc($result)) {
        $uid = $row['uid'];
        $oidlogin = $row['oidlogin'];
        $is_admin = $row['is_admin'];
        $timest = $row['timest'];
        $fiat = $row['fiat'];
        $btc = $row['btc'];
        $committed = fetch_committed_balances($uid);
        $c_fiat = $committed[CURRENCY];
        $c_btc = $committed['BTC'];
        $t_fiat = gmp_add($fiat, $c_fiat);
        $t_btc = gmp_add($btc, $c_btc);
        if ($uid == '1')
            $uid = "fees";
        else
            $count_users++;

        if ($omit_zero_balances && $fiat == 0 && $c_fiat == 0 && $btc == 0 && $c_btc == 0)
            continue;

        if ($first) {
            $first = false;

            echo "<table class='display_data'>\n";
            show_users_header();
        }

        $fiat_total   = gmp_add($fiat_total,   $fiat);
        $c_fiat_total = gmp_add($c_fiat_total, $c_fiat);
        $t_fiat_total = gmp_add($t_fiat_total, $t_fiat);
        $btc_total   = gmp_add($btc_total,   $btc);
        $c_btc_total = gmp_add($c_btc_total, $c_btc);
        $t_btc_total = gmp_add($t_btc_total, $t_btc);

        if ($uid != 'fees') {
            $count_funded_users++;
            if ($fiat   < $tiny_fiat &&
                $c_fiat < $tiny_fiat &&
                $btc    < $tiny_btc  &&
                $c_btc  < $tiny_btc) {
                $count_low_balance_users++;
                if ($omit_very_low_balances)
                    continue;
            }
            $count_shown_users++;
        }

        if ($uid == 'fees')
            $url = "?page=commission";
        else
            $url = "?page=statement&user=$uid";

        if ($is_admin)
            active_table_row('me', $url);
        else
            active_table_row('active', $url);

        echo "<td>$uid</td>";
//      echo "<td>$oidlogin</td>";
        echo "<td class='right'>", internal_to_numstr($fiat,   FIAT_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($c_fiat, FIAT_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($t_fiat, FIAT_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($btc,    BTC_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($c_btc,  BTC_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($t_btc,  BTC_PRECISION), "</td>";
//      echo "<td>$timest</td>";
        echo "</tr>\n";

        if (!($count_shown_users % RESHOW_COLUMN_HEADINGS_AFTER_ROWS))
            show_users_header();
    }

    if (!$first) {
        echo "<tr><td></td><td class='right'>--------</td><td class='right'>--------</td><td class='right'>--------</td><td class='right'>--------</td><td class='right'>--------</td><td class='right'>--------</td></tr>\n";
        active_table_row('me', "?page=statement&user=all");
        echo "<td></td>";
        echo "<td class='right'>", internal_to_numstr($fiat_total,   FIAT_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($c_fiat_total, FIAT_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($t_fiat_total, FIAT_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($btc_total,    BTC_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($c_btc_total,  BTC_PRECISION), "</td>";
        echo "<td class='right'>", internal_to_numstr($t_btc_total,  BTC_PRECISION), "</td>";
        echo "</tr>\n";
        echo "</table>\n";
        echo "<p>" . _("Admins are shown in bold type, and at the top of the table.") . "</p>\n";
    }

    echo "<p>" . sprintf(_("There are %s users with funds, and %s in total."),
                         $count_funded_users,
                         $count_users) . "</p>\n";
    if ($omit_very_low_balances && $count_low_balance_users)
        echo "<p>" . sprintf(_("%d user(s) have very low balances, and aren't shown above."),
                             $count_low_balance_users) . "</p>\n";

    $balance0 = bitcoin_get_balance('*', 0);
    $balance1 = bitcoin_get_balance('*', 1);
    $balance = bitcoin_get_balance('', 0);

    $unconfirmed = gmp_sub($balance0, $balance1);
    echo "<p>" . sprintf(_("The Bitcoin wallet has %s BTC"), internal_to_numstr($balance0, BTC_PRECISION));
    if (gmp_cmp($unconfirmed, 0) != 0)
        printf(_(", %s BTC of which currently has 0 confirmations"), internal_to_numstr($unconfirmed, BTC_PRECISION));
    echo ".<br/></p>\n";
    if ($balance0 == $balance)
        $balance = $balance0;
    else {
        $pending = gmp_sub($balance0, $balance);
        echo "<p>" . sprintf(_("The main wallet account has %s BTC; other accounts have %s BTC waiting for deposit."),
                             internal_to_numstr($balance, BTC_PRECISION),
                             internal_to_numstr($pending, BTC_PRECISION)) . "</p>";
    }

    // take off the amount that's waiting to be withdrawn.  it's in the wallet, but not in user accounts
    $pending_withdrawal = btc_pending_withdrawal();
    $balance = gmp_sub($balance, $pending_withdrawal);

    if ($pending_withdrawal)
        echo "<p>" . sprintf(_("There are pending BTC withdrawals worth %s BTC, which will reduce the wallet balance to %s BTC."),
                             internal_to_numstr($pending_withdrawal, BTC_PRECISION),
                             internal_to_numstr($balance, BTC_PRECISION)) . "</p>";

    $diff = gmp_sub($t_btc_total, $balance);

    $cmp = gmp_cmp($diff, 0);

    if ($cmp == 0)
        echo "<p>" . _("That's the exact right amount.") . "</p>\n";
    else if ($cmp > 0)
        echo "<p>" .
            sprintf(_("That's %s BTC less than is on deposit."), internal_to_numstr($diff, BTC_PRECISION)) .
            "</p>\n";
    else
        echo "<p>" .
            sprintf(_("That's %s BTC more than is on deposit"), internal_to_numstr(gmp_mul("-1", $diff), BTC_PRECISION)) .
            "</p>\n";

    echo "</div>\n";
}

show_verify_user_form();

show_users();
?>
