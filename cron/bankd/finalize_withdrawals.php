<?php
require_once '../../util.php';

$query = "
    UPDATE requests
    SET status='FINAL'
    WHERE
        req_type='WITHDR'
        AND status='PROCES'
        AND curr_type='" . CURRENCY . "'
    ";
do_query($query);

