<?php

require_once "../config.php";

header('Content-type: text/plain');
readfile(ABSPATH . "/wbx_api.php");

?>
