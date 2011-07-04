<?php
if (isset($_SESSION['uid']) && $_SESSION['uid'])
    $loggedin = true;
else
    $loggedin = false;
?>
                </div>
            </div>
        </div>
    </div>
    <div id='links'>
        <ul>
<?php if (!$loggedin) { ?>
            <li><a href='?page=login'>Login</a>
            Begin here</li>
<?php } ?>

            <li><a href='?page='>Trade</a>
            Buy and sell</li>

<?php if ($loggedin) { ?>
            <li><a href='?page=profile'>Profile</a>
            Dox on you</li>
            <li><a href='?page=deposit'>Deposit</a>
            Top up your account</li>
            <li><a href='?page=withdraw'>Withdraw</a>
            Take out money</li>
            <li><a href='?page=orderbook'>Orderbook</a>
            Show orders</li>
<?php } else { ?>
            <li><a href='?page=orderbook'>Orderbook</a>
            Show orders</li>
<?php } ?>

            <li><a href='?page=help'>Help</a>
            Seek support</li>
<?php if ($loggedin) { ?>
            <li><a href='?page=logout'>Logout</a>
            End this session</li>
<?php } ?>
        </ul>
    </div>
    <!--<div id='languages'>
        <a href='google.com'>en</a> &#183; <a href='eo.google.com'>eo</a> &#183; <a href='fff'>fr</a>
    </div>-->
</body>
</html>

