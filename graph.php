<?php

require_once ABSPATH . '/ezcomponents/Base/src/ezc_bootstrap.php';

function format_exponential_axis_label($x) {
    $vals = explode('^', $x);
    return sprintf("%.0f", pow($vals[0], $vals[1]));
}

function get_funds_graph_data()
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

function get_users_graph_data()
{
    $users = array();

    $query = "
        SELECT " .
            sql_format_date('timest') . " AS timest2
        FROM
            users
        WHERE
            uid != 1
        ORDER BY
            timest;
    ";

    $result = do_query($query);
    $count = 0;
    while ($row = mysql_fetch_array($result)) {
        $timest = $row['timest2'];
        $count++;

        $users[$timest] = $count;
    }

    return $users;
}

class customPalette extends ezcGraphPalette
{
    protected $axisColor = '#000000';
    protected $majorGridColor = '#000000BB';
    protected $minorGridColor = '#000000EE';
    protected $dataSetColor = array('#3465A410', '#2E7A0610');
    protected $dataSetSymbol = ezcGraph::NO_SYMBOL;
    protected $fontName = 'sans-serif';
    protected $fontColor = '#000';
    protected $chartBackground = '#7ad3af';
    // protected $chartBorderColor = '#6ac39f';
    protected $chartBorderWidth = 10;
}

function show_funds_graph($log_axis = -1, $x = 0, $y = 0)
{
    global $is_logged_in, $is_admin;

    if (!$is_admin) {
        show_header('graph', $is_logged_in);
        throw new Error("Bad Argument", "You can't view that graph type");
    }

    if ($log_axis == -1) $log_axis = isset($_GET['log']) ? get('log') : 1;
    if (!$x) $x = isset($_GET['x']) ? get('x') : 720;
    if (!$y) $y = isset($_GET['y']) ? get('y') : 500;

    if (!isset($_GET['svg'])) {
        show_header('graph', $is_logged_in);

        echo ("<div class='content_box'>\n" .
              "<h3>Funds on the Exchange</h3>\n" .
              "<p>\n" .
              "<iframe src='?page=graph&type=funds&x=$x&y=$y&log=$log_axis&svg' type='image/svg+xml' width='$x' height='$y' scrolling='no' frameborder='0'>\n" .
              "</iframe>\n" .
              "</p>\n" .
              "</div>\n");
        return;
    }

    $graph = new ezcGraphLineChart();
    $graph->palette = new customPalette();
    $graph->options->fillLines = 180;
    $graph->options->font->maxFontSize = 12;
    $graph->legend->position = ezcGraph::BOTTOM;

    $graph->xAxis = new ezcGraphChartElementDateAxis();
    $graph->xAxis->dateFormat = 'j M';
    $graph->xAxis->interval = 60*60*24*7;

    if ($log_axis) {
        $graph->yAxis = new ezcGraphChartElementLogarithmicalAxis();
        $graph->yAxis->base = pow(10, 1/2);;
        $graph->yAxis->logarithmicalFormatString = '%1$f^%2$f';
        $graph->yAxis->labelCallback = "format_exponential_axis_label";
    }

    list ($btc, $fiat) = get_funds_graph_data();

    $graph->data[CURRENCY_FULL_PLURAL] = new ezcGraphArrayDataSet($fiat);
    $graph->data['Bitcoins'] = new ezcGraphArrayDataSet($btc);

    $graph->renderToOutput($x, $y);
    exit();                     // we don't want the footer
}

function show_users_graph($x = 0, $y = 0)
{
    global $is_logged_in, $is_admin;

    if (!$is_admin) {
        show_header('graph', $is_logged_in);
        throw new Error("Bad Argument", "You can't view that graph type");
    }

    if (!$x) $x = isset($_GET['x']) ? get('x') : 720;
    if (!$y) $y = isset($_GET['y']) ? get('y') : 500;

    if (!isset($_GET['svg'])) {
        show_header('graph', $is_logged_in);

        echo ("<div class='content_box'>\n" .
              "<h3>Users on the Exchange</h3>\n" .
              "<p>\n" .
              "<iframe src='?page=graph&type=users&x=$x&y=$y&svg' type='image/svg+xml' width='$x' height='$y' scrolling='no' frameborder='0'>\n" .
              "</iframe>\n" .
              "</p>\n" .
              "</div>\n");
        return;
    }

    $graph = new ezcGraphLineChart();
    $graph->palette = new customPalette();
    $graph->options->fillLines = 180;
    $graph->options->font->maxFontSize = 12;
    $graph->legend->position = ezcGraph::BOTTOM;

    $graph->xAxis = new ezcGraphChartElementDateAxis();
    $graph->xAxis->dateFormat = 'j M';
    $graph->xAxis->interval = 60*60*24*7;

    $users = get_users_graph_data();

    $graph->data['Users'] = new ezcGraphArrayDataSet($users);

    $graph->renderToOutput($x, $y);
    exit();                     // we don't want the footer
}

function graph_main()
{
    global $is_logged_in, $is_admin;

    if (isset($_GET['type']))
        switch(get('type')) {
        case 'funds':
            show_funds_graph();
            break;
        case 'users':
            show_users_graph();
            break;
        default:
            show_header('graph', $is_logged_in);
            throw new Error("Bad Argument", "Unknown graph type");
        }
    else {
        show_header('graph', $is_logged_in);

        echo "<div class='content_box'>\n";
        echo "<h3>Graphs</h3>\n";
        echo "<p>Pick a graph type:</p>\n";
        echo "<ul>\n";
        echo "<li><a href='?page=graph&type=funds&log=0'>funds (linear axis)</a></li>\n";
        echo "<li><a href='?page=graph&type=funds'>funds (log axis)</a></li>\n";
        echo "<li><a href='?page=graph&type=users'>users</a></li>\n";
        echo "</li></p></div>\n";
    }
}

graph_main();

?>
