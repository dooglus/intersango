<?php
?>
<!DOCTYPE html>
<html>
<head>
    <title>Boo</title>
    <?php if (!$page) { ?>
    <script type='text/javascript' src='jquery-1.4.4.min.js'></script>
    <script type='text/javascript' src='exchanger.js'></script>
    <?php 
        require "$abspath/db.php";
        $currencies = array('BTC', 'GBP');
        $rates = array();
        foreach ($currencies as $curr_a) {
            $rates_a = array();
            foreach ($currencies as $curr_b) {
                if ($curr_a == $curr_b)
                    continue;
                $exchange_fields = calc_exchange_rate($curr_b, $curr_a);        
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
</head>

<?php if (!$page) { ?>
<body onload='set_currency_in("gbp"); set_currency_out("btc");'>
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
