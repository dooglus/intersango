<?php

require_once ABSPATH . '/ezcomponents/Base/src/ezc_bootstrap.php';

function format_exponential_axis_label($x) {
    $vals = explode('^', $x);
    return sprintf("%.0f", pow($vals[0], $vals[1]));
}

function get_transfers()
{
    $btc = array();
    $fiat = array();

    $query = "
        SELECT
            req_type, amount, curr_type, " . sql_format_date('timest') . " AS timest2
        FROM
            requests
        WHERE
            status != 'CANCEL'
        ORDER BY
            timest;
    ";

    $result = do_query($query);
    $btc_sum = 0;
    $fiat_sum = 0;
    while ($row = mysql_fetch_array($result)) {
        $req_type = $row['req_type'];
        $amount = $row['amount'];
        $curr_type = $row['curr_type'];
        $timest = $row['timest2'];

        if ($req_type == 'WITHDR')
            $amount = gmp_mul(-1, $amount);

        if ($curr_type == 'BTC') {
            $btc_sum = gmp_add($btc_sum, $amount);
            $btc[$timest] = internal_to_numstr($btc_sum);
        } else {
            $fiat_sum = gmp_add($fiat_sum, $amount);
            $fiat[$timest] = internal_to_numstr($fiat_sum);
        }
    }

    return array($btc, $fiat);
}

function show_graph()
{
    $symbol = ezcGraph::NO_SYMBOL;
    // $symbol = ezcGraph::BULLET;

    $graph = new ezcGraphLineChart();
    $graph->options->fillLines = 128;
    $graph->title = 'Funds on the Exchange';
    $graph->legend->position = ezcGraph::BOTTOM;

    $graph->xAxis = new ezcGraphChartElementDateAxis();
    $graph->xAxis->dateFormat = 'j M';
    $graph->xAxis->interval = 60*60*24*7;

    $graph->yAxis = new ezcGraphChartElementLogarithmicalAxis();
    $graph->yAxis->base = pow(10, 1/2);;
    $graph->yAxis->logarithmicalFormatString = '%1$f^%2$f';
    $graph->yAxis->labelCallback = "format_exponential_axis_label";

    $graph->title->font->maxFontSize = 20;
    $graph->options->font->maxFontSize = 12;

    list ($btc, $fiat) = get_transfers();

    $graph->data[CURRENCY_FULL_PLURAL] = new ezcGraphArrayDataSet($fiat);
    $graph->data[CURRENCY_FULL_PLURAL]->symbol = $symbol;

    $graph->data['Bitcoins'] = new ezcGraphArrayDataSet($btc);
    $graph->data['Bitcoins']->symbol = $symbol;

    $graph->palette = new ezcGraphPaletteEzGreen();

    $graph->renderToOutput(1200, 620);
}

show_graph();

exit();                         // we don't want the footer

?>
