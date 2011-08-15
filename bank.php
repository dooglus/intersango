<?php

include_once "bank/xero.php";
include_once "bank/bank_config.php";

// instantiate the Xero class with your key, secret and paths to your RSA cert and key
// the last argument is optional and may be either "xml" or "json" (default)
// "xml" will give you the result as a SimpleXMLElement object, while 'json' will give you a plain array object
$xero = new Xero(XERO_KEY, XERO_SECRET, ABSPATH . "/bank/publickey.cer", ABSPATH . "/bank/privatekey.pem", 'json');

function list_accounts($xero)
{
    echo "<div class='content_box'>\n";
    echo "<h3>Accounts</h3>\n";
    $result = $xero->Accounts();
    if ($result['Status'] == 'OK') {
        echo "<p>list of accounts:</p><ul>\n";
        foreach ($result['Accounts']['Account'] as $account) {
            echo "<li>", $account['AccountID'], " : ", $account['Name'], "<br/>\n";
        }
        echo "</ul>\n";
    }
    echo "</div>\n";
}

function show_statement($xero, $account, $from = '', $to = '')
{
    $result = $xero->BankStatement("?bankaccountid=$account$from$to");
    if ($result['Status'] == 'OK') {
        echo "<div class='content_box'>\n";
        echo "<h3>Statement</h3>\n";

        $report = $result['Reports']['Report'];
        echo "<p>Titles: ", implode($report['ReportTitles']['ReportTitle'], ' - '), "</p>\n";
        echo "<p>ReportDate: ", $report['ReportDate'], "</p>\n";
        $data = $report['Rows']['Row'];

        echo "<table border='2' cellpadding='5px'>\n";
        // echo $data[0]['RowType'], "\n";
        echo "<rowset>";
        foreach ($data[0]['Cells']['Cell'] as $cell)
            echo "<th>", $cell['Value'], "</th>";
        echo "</rowset>";
        // echo $data[1]['RowType'], "\n";
        foreach ($data[1]['Rows']['Row'] as $row) {
            echo "<tr>";
            foreach ($row['Cells']['Cell'] as $cell) {
                if (isset($cell['Value'])) {
                    $value = $cell['Value'];
                    $value = str_replace('T00:00:00', '', $value);
                } else
                    $value = '';
                echo "<td>$value</td>";
            }
            echo "</tr>";
            echo "\n";
        }
        echo "</table></div>\n";
    }
}

function show_withdrawals()
{
    echo "<div class='content_box'>\n";
    echo "<h3>Withdraw requests</h3>\n";
    $result = do_query("SELECT * FROM requests WHERE req_type = 'WITHDR' AND curr_type = 'AUD'");
    $first = true;
    while ($row = mysql_fetch_assoc($result)) {
        if ($first) {
            $first = false;

            echo "<table class='display_data'>\n";
            echo "<tr><th>User</th><th>Amount</th></tr>\n";
        }
        $uid = $row['uid'];
        $amount = internal_to_numstr($row['amount']);
        echo "<tr><td>$uid</td><td>$amount</td></tr>\n";
    }

    if ($first)
        echo "<p>No pending withdrawals.</p>\n";
    else
        echo "</table>\n";

    echo "</div>\n";
}

$from = "&fromDate=1 Jan 2011";
# $to = "&toDate=31 Dec 2011";

show_statement($xero, ACCOUNT, $from);
// list_accounts($xero);
show_withdrawals();

?>
