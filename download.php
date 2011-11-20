<?php

function download_user_document()
{
    $uid = post('uid');
    $file = post('file', '-');

    $path = ABSPATH . "/docs/$uid/$file";

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Type: application/pgp-encrypted");
    header("Content-Disposition: attachment; filename=\"$uid--$file\"");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($path));

    readfile($path);
}

download_user_document();
exit();                     // we don't want the footer

?>
