<?php
require '../../util.php';

$query = "
    UPDATE requests
    SET status='FINAL'
    WHERE
        req_type='WITHDR'
        AND status='PROCES'
        AND curr_type='AUD'
    ";
do_query($query);

