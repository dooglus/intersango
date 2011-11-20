<?php

function show_link($page, $title, $text, $admin=0)
{
    if ($admin)
        echo "            <li><a style='color: red;' href='";
    else
        echo "            <li><a href='";
    echo URLROOT, "?page=$page'>$title</a>$text</li>\n";
}

function show_links($is_logged_in, $is_admin, $is_verified)
{
    $show_duo = 0;
    if ($is_logged_in) {
        require_once 'db.php';
        $result = do_query("SELECT use_duo FROM users WHERE uid=$is_logged_in");
        $row = get_row($result);
        $show_duo = !$row['use_duo'];
    }

    if (!$is_logged_in) show_link('login',       _('Login'),      _('Begin here')                                      );
    show_link                    ('news',        _('News'),       _("What's new?")                                     );
    show_link                    ('trade',       _('Trade'),      _('Buy and sell')                                    );
    if ($is_logged_in)  show_link('profile',     _('Profile'),    _('Dox on you')                                      );
    if ($is_logged_in)  show_link('statement',   _('Statement'),  _('Chronological ledger')                            );
    if ($is_logged_in)  show_link('deposit',     _('Deposit'),    _('Top up your account')                             );
    if ($is_logged_in)  show_link('withdraw',    _('Withdraw'),   _('Take out money')                                  );
    show_link                    ('orderbook',   _('Orderbook'),  _('Show orders')                                     );
    if ($show_duo)      show_link('turn_on_duo', _('Security'),   _('Use two-factor authentification')                 );

    if ($is_logged_in && !$is_verified)
                        show_link('identity',    _('Identify'),   _('Upload ID to get your account verified')          );

    if (CHARTS_PAGE)    printf("<li><a target='_blank' href='%s'>%s</a>%s\n",
                               CHARTS_PAGE,      _('Charts'),     _('From bitcoincharts.com')                          );
    show_link                    ('help',        _('Help'),       _('Seek support')                                    );
    if ($is_admin)      show_link('users',       _('Users'),      _('Show registered users'),                         1);
    if ($is_admin)      show_link('add_cash',    _('Add cash'),   _('Deposit using bank statement'),                  1);
    if ($is_admin)      show_link('commission',  _('Commission'), _('Show commission statement'),                     1);
    if ($is_admin)      show_link('bank',        _('Bank'),       _('Show bank statement &amp; pending withdrawals'), 1);
    if ($is_admin)      show_link('freeze',      _('Freeze'),     _('Stop activity on the exchange'),                 1);
    if ($is_admin)      show_link('graph',       _('Charts'),     _('Various admin graphs'),                          1);
    if ($is_logged_in)  show_link('logout',      _('Logout'),     _('End this session')                                );

    echo "<br/><g:plusone annotation=\"bubble\" href=\"https://www.worldbitcoinexchange.com/\"></g:plusone>\n";
}

function show_footer($is_logged_in, $is_admin, $is_verified)
{
    if (isset($_GET['fancy'])) {
        echo "</div></body></html>\n";
        return;
    }
?>
                </div>
            </div>
        </div>
    </div>
    <div id='links'>
        <ul>
<?php show_links($is_logged_in, $is_admin, $is_verified); ?>
        </ul>
    </div>
    <!--<div id='languages'>
        <a href='google.com'>en</a> &#183; <a href='eo.google.com'>eo</a> &#183; <a href='fff'>fr</a>
    </div>-->
</body>
</html>
<?php } ?>
