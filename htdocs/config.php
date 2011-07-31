<?php
$abspath = '/home7/worldbit/programs/intersango';
require "$abspath/errors.php";
enable_errors();

// how many confirmations we need on incoming bitcoin transfers before adding them to the user accounts
function confirmations_for_deposit() { return 4; }

?>
