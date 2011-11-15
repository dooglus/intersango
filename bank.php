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
        echo "<h3>" . _("Statement") . "</h3>\n";

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
    echo "<h3>" . _("Withdraw requests") . "</h3>\n";
    $result = do_query("
        SELECT requests.reqid as reqid, uid, amount, " . sql_format_date("timest") . " as timest, name, bank, acc_num, sort_code
        FROM requests
        JOIN uk_requests
        ON uk_requests.reqid = requests.reqid
        WHERE req_type = 'WITHDR'
          AND curr_type = '" . CURRENCY . "'
          AND status = 'VERIFY'");
    $first = true;
    while ($row = mysql_fetch_assoc($result)) {
        if ($first) {
            $first = false;

            echo "<table class='display_data'>\n";
            echo "<tr>";
            // echo "<th>User</th>";
            echo "<th>" . CURRENCY . "</th>";
            echo "<th>Time</th>";
            echo "<th>Name</th>";
            echo "<th>Bank</th>";
            echo "<th>Account#</th>";
            echo "<th>BSB</th>";
            echo "</tr>\n";
        }
        $reqid = $row['reqid'];
        // $uid = $row['uid'];
        $amount = internal_to_numstr($row['amount']);
        $timest = $row['timest'];
        $name = $row['name'];
        $bank = $row['bank'];
        $acc_num = $row['acc_num'];
        $sort_code = $row['sort_code'];
        echo "<tr>";
        echo active_table_row("me", "?page=view_request&reqid=$reqid&show_finish");
        // echo "<td>$uid</td>";
        echo "<td>$amount</td>";
        echo "<td>$timest</td>";
        echo "<td>$name</td>";
        echo "<td>$bank</td>";
        echo "<td>$acc_num</td>";
        echo "<td>$sort_code</td>";
        echo "</tr>\n";
    }

    if ($first)
        echo "<p>No pending withdrawals.</p>\n";
    else
        echo "</table>\n";

    echo "</div>\n";
}

$from = "&fromDate=1 Jan 2011";
// $to = "&toDate=31 Dec 2011";

// show_statement($xero, ACCOUNT, $from);
// list_accounts($xero);
show_withdrawals();

?>
