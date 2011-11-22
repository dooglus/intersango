<?php

if (isset($_POST['uid'])) {
    if (isset($_POST['csrf_token'])) {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf","csrf token mismatch!");
    }
    else
        throw new Error("csrf","csrf token missing!");
}

function show_user_documents_for_user($uid, $verified = 'unknown')
{
    if ($verified == 'unknown')
        $verified = get_verified_for_user($uid);

    $dir = ABSPATH . "/docs/$uid";
    echo "<div class='content_box'>\n";
    echo "<h3>$uid</h3>\n";

    $readme = ABSPATH . "/docs/$uid/00-README.txt";
    if (!file_exists($readme)) {
        echo "<p>" . sprintf(_("User %s hasn't uploaded anything."), $uid) . "</p>\n";
        echo "</div>\n";
        return;
    }

    echo "<pre>\n";
    $fp = fopen($readme, 'r');
    while ($line = fgets($fp)) {
        $line = rtrim($line);
        // echo "    $line\n";
        // $line = substr($line, 0, 25) . substr($line, 35);
        echo "  $line\n";
    }
    echo "</pre>\n";

    echo "<p>\n";
    $dp = opendir($dir);
    $candidates = array();
    while ($file = readdir($dp)) {
        if ($file == '00-README.txt' || $file == '.' || $file == '..') continue;
        echo "<form action='?page=download' method='post'>\n";
        echo "<input type='hidden' name='csrf_token' value=\"" . $_SESSION['csrf_token'] . "\" />\n";
        echo "<input type='hidden' name='uid' value='$uid' />\n";
        echo "<input type='hidden' name='file' value='$file' />\n";
        echo "<input type='submit' value='$file' />\n";
        echo "</form>\n";
    }

    echo "<form action='' method='post'>\n";
    echo "<input type='hidden' name='csrf_token' value=\"" . $_SESSION['csrf_token'] . "\" />\n";
    printf("<input type='hidden' name='%s' value='%s' />\n", ($verified ? 'unverify' : 'verify'), $uid);
    printf("<input type='submit' value='* %s USER %s *' />\n", ($verified ? 'UNVERIFY' : 'VERIFY'), $uid);
    echo "</form>\n";

    echo "</p>\n";
    echo "</div>\n";
}

function show_user_documents()
{
    echo "<div class='content_box'>\n";
    $verified = isset($_GET['verified']) ? 1 : 0;
    if ($verified) {
        echo "<h3>User Documents for Verified Users</h3>\n";
        echo "<p><a href=\"?page=docs\">View docs for unverified users</a></p>\n";
    } else {
        echo "<h3>User Documents for New Users</h3>\n";
        echo "<p><a href=\"?page=docs&verified\">View docs for verified users</a></p>\n";
    }
    echo "</div>\n";

    $users = array();
    $result = do_query("SELECT uid FROM users WHERE verified = $verified");
    while ($row = mysql_fetch_array($result))
        array_push($users, $row['uid']);

    $dir = ABSPATH . "/docs";
    $dp = opendir($dir);
    $candidates = array();
    $first = true;
    while ($uid = readdir($dp)) {
        if (!in_array($uid, $users)) continue;
        $path = "$dir/$uid";
        if (!is_dir($path)) continue;
        $first = false;
        $candidates[$uid] = filemtime($path);
    }

    if ($first) {
        echo "<div class='content_box'>\n";
        if ($verified)
            echo "<p>" . _("There are no documents for verified users.") . "</p>\n";
        else
            echo "<p>" . _("There are no documents pending review.") . "</p>\n";
        echo "</div>\n";
    } else {
        // newest first for pending docs, else in order of UID
        if ($verified)
            ksort($candidates);
        else
            arsort($candidates);

        foreach ($candidates as $uid => $mtime)
            show_user_documents_for_user($uid, $verified);
    }
}

function docs()
{
    if (isset($_POST['verify']))
        verify_user(post('verify'));
    else if (isset($_POST['unverify']))
        unverify_user(post('unverify'));

    if (isset($_GET['uid']))
        show_user_documents_for_user(get('uid'));
    else
        show_user_documents();
}

docs();

?>
