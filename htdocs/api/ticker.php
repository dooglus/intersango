<?php
require_once "../config.php";
require_once ABSPATH . "/util.php";

list ($high, $low, $avg, $vwap, $vol, $last, $buy, $sell) = get_ticker_data();

echo '{"ticker": {';
echo '"high": ' . $high . ', ';
echo '"low": '  . $low  . ', ';
echo '"avg": '  . $avg  . ', ';
echo '"vwap": ' . $vwap . ', ';
echo '"vol": '  . $vol  . ', ';
echo '"last": ' . $last . ', ';
echo '"buy": '  . $buy  . ', ';
echo '"sell": ' . $sell . '}}';
?>
