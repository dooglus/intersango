<?php
require '../errors.php';
require '../util.php';
if (count($argv) < 2) {
    echo "need account name to synchronise\n";
    exit(-1);
}
$uid = cleanup_string($argv[1]);
# check they actually exist
$query = "SELECT 1 FROM users WHERE uid='$uid'";
$result = do_query($query);
if (has_results($result)) {
    sync_to_bitcoin($uid);
    echo "Done.\n";
}
else
    echo "User $uid doesn't exist.\n";
?>
