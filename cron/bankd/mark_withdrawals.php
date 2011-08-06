<?php
require_once '../../util.php';

$query = "
    UPDATE requests
    SET status='PROCES'
    WHERE
        req_type='WITHDR'
        AND status='VERIFY'
        AND curr_type='AUD'
    ";
do_query($query);

