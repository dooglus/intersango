<?php
if (isset($_SESSION['uid']) && $_SESSION['uid'])
    $loggedin = true;
else
    $loggedin = false;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Boo</title>
    <?php if ($loggedin) { ?>
    <script type="text/javascript" src="jquery-1.4.4.min.js"></script>
    <script type="text/javascript" src="exchanger.js"></script>
    <?php } ?>
    <link rel="stylesheet" type="text/css" href="style.css" />
</head>

<?php if ($loggedin) { ?>
<body onload='set_currency_in("gbp"); set_currency_out("btc");'>
<?php } else { ?>
<body>
<?php } ?>
    <img id='flower' src='images/flower.png' />
    <img id='header' src='images/header.png' />
    <img id='skyline' src='images/skyline.png' />
    <div id='content'>
        <div id='content_sideshadow'>
