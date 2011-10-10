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

function show_funds_graph($x = 0, $y = 0)
{
    global $is_logged_in, $is_admin;

    if (!$is_admin) return;

    if (!$x)
        if (isset($_GET['x']))
            $x = get('x');
        else
            $x = 720;

    if (!$y)
        if (isset($_GET['y']))
            $y = get('y');
        else
            $y = 500;

    if (!isset($_GET['svg'])) {
        show_header('graph', $is_logged_in);

        echo "<div class='content_box'>\n";
        echo "<h3>Graphs</h3>\n";
        echo "<p>\n";
        echo "<iframe src='?page=graph&type=funds&x=$x&y=$y&svg' type='image/svg+xml' width='$x' height='$y' scrolling='no' frameborder='0' />\n";
        echo "</p>\n";
        echo "</div>\n";
        return;
    }

    $symbol = ezcGraph::NO_SYMBOL;
    // $symbol = ezcGraph::BULLET;

    $graph = new ezcGraphLineChart();
    $graph->options->fillLines = 128;
    // $graph->title = 'Funds on the Exchange';
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

    $graph->renderToOutput($x, $y);
}

function graph_main()
{
    global $is_logged_in, $is_admin;

    if (isset($_GET['type'])) {
        switch(get('type')) {
        case 'funds':
            show_funds_graph();
            break;
        default:
            show_header('graph', $is_logged_in);
            return;
        }
        exit();                 // we don't want the footer
    } else {
        show_header('graph', $is_logged_in);

        echo "<div class='content_box'>\n";
        echo "<h3>Graphs</h3>\n";
        echo "<p>which?</p>\n";
        echo "<p><a href='?page=graph&type=funds&x=800&y=500'>funds</a>\n";
        show_funds_graph(350,250);
        echo "</p></div>\n";
    }
}

graph_main();

?>
