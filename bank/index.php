<?php

include_once "xero.php";
include_once "bank_config.php";

global $abspath;

// instantiate the Xero class with your key, secret and paths to your RSA cert and key
// the last argument is optional and may be either "xml" or "json" (default)
// "xml" will give you the result as a SimpleXMLElement object, while 'json' will give you a plain array object
$xero = new Xero(XERO_KEY, XERO_SECRET, "$abspath/bank/publickey.cer", "$abspath/bank/privatekey.pem", 'json');

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

$from = "&fromDate=1 Jan 2011";
# $to = "&toDate=31 Dec 2011";

show_statement($xero, ACCOUNT, $from);
list_accounts($xero);

?>
