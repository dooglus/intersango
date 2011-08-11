<?php

function show_link($page, $title, $text, $admin=0)
{
    global $urlroot;
    if ($admin)
        echo "            <li><a style='color: red;' href='";
    else
        echo "            <li><a href='";
    echo $urlroot, "?page=$page'>$title</a>$text</li>\n";
}

function show_links($is_logged_in, $is_admin)
{
    $show_duo = 0;
    if ($is_logged_in) {
        require_once 'db.php';
        $result = do_query("SELECT use_duo FROM users WHERE uid=$is_logged_in");
        $row = get_row($result);
        $show_duo = !$row['use_duo'];
    }

    if (!$is_logged_in) show_link('login',        'Login',        'Begin here'                     );
    show_link                    ('trade',        'Trade',        'Buy and sell'                   );
    if ($is_logged_in)  show_link('profile',      'Profile',      'Dox on you'                     );
    if ($is_logged_in)  show_link('deposit',      'Deposit',      'Top up your account'            );
    if ($is_logged_in)  show_link('withdraw',     'Withdraw',     'Take out money'                 );
    show_link                    ('orderbook',    'Orderbook',    'Show orders'                    );
    if ($show_duo)      show_link('turn_on_duo',  'Security',     'Use two-factor authentification');
    show_link                    ('help',         'Help',         'Seek support'                   );
    if ($is_admin)      show_link('users',        'Users',        'Show registered users',        1);
    if ($is_admin)      show_link('commission',   'Commission',   'Show commission statement',    1);
    if ($is_admin)      show_link('bank',         'Bank',         'Show bank statement'          ,1);
    if ($is_admin)      show_link('freeze',       'Freeze',       'Stop activity on the exchange',1);
    if ($is_logged_in)  show_link('logout',       'Logout',       'End this session'               );
}

function show_footer($is_logged_in, $is_admin)
{
?>
                </div>
            </div>
        </div>
    </div>
    <div id='links'>
        <ul>
<?php show_links($is_logged_in, $is_admin); ?>
        </ul>
    </div>
    <!--<div id='languages'>
        <a href='google.com'>en</a> &#183; <a href='eo.google.com'>eo</a> &#183; <a href='fff'>fr</a>
    </div>-->
</body>
</html>
<?php } ?>
