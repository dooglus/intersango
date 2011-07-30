<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
    <title>Intersango Bitcoin Exchange</title>
    <?php if (!$page) { ?>
    <script type='text/javascript' src='jquery-1.4.4.min.js'></script>
    <script type='text/javascript' src='exchanger.js'></script>
    <?php 
        require "$abspath/util.php";
        $currencies = array('BTC', 'AUD');
        $rates = array();
        foreach ($currencies as $curr_a) {
            $rates_a = array();
            foreach ($currencies as $curr_b) {
                if ($curr_a == $curr_b)
                    continue;
                $exchange_fields = calc_exchange_rate($curr_b, $curr_a, BASE_CURRENCY::B);        
                if ($exchange_fields) {
                    $curr_b = strtolower($curr_b);
                    $rates_a[$curr_b] = (float)$exchange_fields[2];
                }
            }
            $curr_a = strtolower($curr_a);
            $rates[$curr_a] = $rates_a;
        }
        echo "    <script type='text/javascript'>\n";
        echo "        exchange_rates = ".json_encode($rates).";\n";
        echo "    </script>\n";
    } ?>
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="icon" type="image/png" href="favicon.png" />
</head>

<?php if (!$page) { ?>
<body onload='set_currency_in("aud"); set_currency_out("btc");'>
<?php } else { ?>
<body>
<?php } ?>
    <img id='flower' src='images/flower.png' />
    <img id='header' src='images/header.png' />
    <img id='skyline' src='images/skyline.png' />
    <div id='main_pane'>
        <div id='links_bg'>
            <div id='content'>
                <div id='content_sideshadow'>
