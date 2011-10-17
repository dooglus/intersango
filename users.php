<?php

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
    $count_users = $count_funded_users = $count_low_balance_users = 0;

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
            echo "<tr>";
            echo "<th></th>";
//          echo "<th></th>";
            echo "<th colspan='3' style='text-align: center;'>" . CURRENCY . "</th>";
            echo "<th colspan='3' style='text-align: center;'>BTC</th>";
            echo "</tr>\n";
            echo "<tr>";
            echo "<th>" . _("UID") . "</th>";
//          echo "<th>" . _("OID") . "</th>";
            echo "<th class='right'>" . _("On Hand") . "</th>";
            echo "<th class='right'>" . _("In Book") . "</th>";
            echo "<th class='right'>" . _("Total") . "</th>";
            echo "<th class='right'>" . _("On Hand") . "</th>";
            echo "<th class='right'>" . _("In Book") . "</th>";
            echo "<th class='right'>" . _("Total") . "</th>";
//          echo "<th>" . _("Registered") . "</th>";
            echo "</tr>\n";
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

    $bitcoin = connect_bitcoin();
    $balance0 = $bitcoin->getbalance('*', 0);
    $balance1 = $bitcoin->getbalance('*', 1);
    $balance = $bitcoin->getbalance('', 1);

    $unconfirmed = gmp_sub($balance0, $balance1);
    echo "<p>" . sprintf(_("The Bitcoin wallet has %s BTC"), internal_to_numstr($balance0, BTC_PRECISION));
    if (gmp_cmp($unconfirmed, 0) != 0)
        printf(_(", %s BTC of which currently has 0 confirmations"), internal_to_numstr($unconfirmed, BTC_PRECISION));
    echo ".<br/></p>\n";
    if ($balance0 != $balance)
        echo "<p>" . sprintf(_("The main wallet account has %s BTC"), internal_to_numstr($balance, BTC_PRECISION)) . "</p>";
    else
        $balance = $balance0;

    $diff = gmp_sub($t_btc_total, $balance);

    $cmp = gmp_cmp($diff, 0);

    if ($cmp == 0)
        echo "<p>" . _("That's the exact right amount.") . "</p>\n";
    else if ($cmp > 0)
        echo "<p>" .
            sprintf(_("That's %s BTC less than is on deposit"), internal_to_numstr($diff, BTC_PRECISION)) .
            "</p>\n";
    else
        echo "<p>" .
            sprintf(_("That's %s BTC more than is on deposit"), internal_to_numstr(gmp_mul("-1", $diff), BTC_PRECISION)) .
            "</p>\n";

    echo "</div>\n";
}

show_users();
?>
