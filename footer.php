<?php

function show_link($page, $title, $text)
{
    global $urlroot;
    echo "            <li><a href='", $urlroot, "?page=$page'>$title</a>$text</li>\n";
}

function show_links()
{
    if (isset($_SESSION['uid']) && $_SESSION['uid']) {
        $loggedin = true;
        $uid = $_SESSION['uid'];
    } else
        $loggedin = false;

    $show_duo = 0;
    if ($loggedin) {
        require_once '../db.php';
        $result = do_query("SELECT use_duo FROM users WHERE uid=$uid");
        $row = get_row($result);
        $show_duo = !$row['use_duo'];
    }

    if (!$loggedin) show_link('login',        'Login',        'Begin here'                     );
    show_link                ('trade',        'Trade',        'Buy and sell'                   );
    if ($loggedin) show_link ('profile',      'Profile',      'Dox on you'                     );
    if ($loggedin) show_link ('deposit',      'Deposit',      'Top up your account'            );
    if ($loggedin) show_link ('withdraw',     'Withdraw',     'Take out money'                 );
    show_link                ('orderbook',    'Orderbook',    'Show orders'                    );
    if ($show_duo) show_link ('turn_on_duo',  'Security',     'Use two-factor authentification');
    show_link                ('help',         'Help',         'Seek support'                   );
    if ($loggedin) show_link ('logout',       'Logout',       'End this session'               );
}
?>
                </div>
            </div>
        </div>
    </div>
    <div id='links'>
        <ul>
<?php show_links(); ?>
        </ul>
    </div>
    <!--<div id='languages'>
        <a href='google.com'>en</a> &#183; <a href='eo.google.com'>eo</a> &#183; <a href='fff'>fr</a>
    </div>-->
</body>
</html>
