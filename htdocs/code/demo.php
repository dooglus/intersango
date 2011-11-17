<?php

require_once "../config.php";

header('Content-type: text/plain');
readfile(ABSPATH . "/demo.php");

?>
