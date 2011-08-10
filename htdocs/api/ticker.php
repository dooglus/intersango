<?php
require_once "../config.php";
require_once "$abspath/util.php";

list ($vol, $buy, $sell, $last) = get_ticker_data();

echo '{"ticker": {';
echo '"vol": ' . $vol . ', ';
echo '"buy": ' . $buy . ', ';
echo '"sell": ' . $sell . ', ';
echo '"last": ' . $last . '}}';

?>

