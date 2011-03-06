<?php
if (isset($_SESSION['uid']) && $_SESSION['uid'])
    $loggedin = true;
else
    $loggedin = false;
?>
        </div>
    </div>
    <div id='links'>
        <ul>
            <li><a href='?page='>Home</a>
            Return home</li>
<?php if ($loggedin) { ?>
            <li><a href='?page=logout'>Logout</a>
            Remove this session</li>
            <li><a href='?page=profile'>Profile</a>
            Dox on you</li>
<?php } else { ?>
            <li><a href='?page=login'>Login</a>
            Begin here</li>
<?php } ?>
            <li><a href='?page=show_orders'>Orderbook</a>
            Show orders</li>
            <li><a href='google.com'>Account history</a>
            Recent history</li>
            <li><a href='google.com'>Add funds</a>
            Top up your account</li>
            <li><a href='google.com'>Withdraw</a>
            Take out money</li>
            <li><a href='google.com'>Help</a>
            How it works</li>
        </ul>
    </div>
    <!--<div id='languages'>
        <a href='google.com'>en</a> &#183; <a href='eo.google.com'>eo</a> &#183; <a href='fff'>fr</a>
    </div>-->
</body>
</html>

