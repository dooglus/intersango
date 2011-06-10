<?php
require '../../util.php';

$query = "
    UPDATE requests
    SET status='PROCES'
    WHERE
        req_type='WITHDR'
        AND status='VERIFY'
        AND curr_type='GBP'
    ";
do_query($query);

$query = "
    SELECT 
        requests.reqid AS reqid,
        amount/100000000 AS amount,
        name,
        bank,
        acc_num,
        sort_code
    FROM
        requests
    JOIN
        uk_requests
    ON
        uk_requests.reqid=requests.reqid
    WHERE
        req_type='WITHDR'
        AND status='PROCES'
        AND curr_type='GBP'
    ";
$result = do_query($query);

while ($row = mysql_fetch_array($result)) {
    $reqid = $row['reqid'];

    echo "javascript:function f(){";
    echo "document.forms[0]['Beneficiary'].value='{$row['name']}';";
    $sortcode = $row['sort_code'];
    $sortcode1 = $sortcode[0] . $sortcode[1];
    $sortcode2 = $sortcode[2] . $sortcode[3];
    $sortcode3 = $sortcode[4] . $sortcode[5];
    echo "document.forms[0]['SortCode1'].value='{$sortcode1}';";
    echo "document.forms[0]['SortCode2'].value='{$sortcode2}';";
    echo "document.forms[0]['SortCode3'].value='{$sortcode3}';";
    echo "document.forms[0]['AccountNumber'].value='{$row['acc_num']}';";
    echo "document.forms[0]['Reference'].value='Britcoin';";
    echo "}f();\n\n";
    
    $amount = $row['amount'];
    $amount = explode('.', $amount);
    $amount_whole = $amount[0];
    $amount_decimal = substr($amount[1], 0, 2);
    echo "javascript:function f(){";
    echo "document.forms[0]['AmountWhole'].value='{$amount_whole}';";
    echo "document.forms[0]['AmountDecimal'].value='{$amount_decimal}';";
    echo "}f();\n\n";

    echo "Reference = {$row['acc_num']}\n";
    echo "Amount = $amount_whole.$amount_decimal\n";
    echo "-----------------------------------\n\n";
    readline('');

    $query = "
        UPDATE requests
        SET status='FINAL'
        WHERE
            reqid='$reqid'
            AND req_type='WITHDR'
            AND status='PROCES'
            AND curr_type='GBP'
        ";
    do_query($query);
}

