<?php

if (empty($_FILES) &&
    empty($_POST) &&
    isset($_SERVER['REQUEST_METHOD']) &&
    strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
    throw new Error(_("Upload Error"),
                    sprintf(_("The upload failed because it was too big.  The maximum combined size is %s.  Please upload large files separately, or try to reduce the file sizes."),
                            post_max_size()));
}

if (isset($_POST['upload_doc']))
    if (isset($_POST['csrf_token'])) {
        if ($_SESSION['csrf_token'] != $_POST['csrf_token'])
            throw new Error("csrf","csrf token mismatch!");
    }
    else
        throw new Error("csrf","csrf token missing!");

function upload_identity_doc($num)
{
    global $is_logged_in;

    $file = "file$num";

    if (!isset($_FILES[$file]))
        return 0;

    $info = $_FILES[$file];
    if ($info['error'])
        return 0;

    $description = post("description$num");
    $filename = cleanup_string(basename($info['name']));
    $type = $info['type'];
    $source = $info['tmp_name'];
    $size = $info['size'];

    $dir = DOCDIR . "/$is_logged_in";
    @mkdir($dir, 0755);
    $base = "$filename";
    $index = $dir . "/00-README.txt";

    $dest = $base;
    $count = 1;
    while (file_exists($dir . "/$dest") || file_exists($dir . "/$dest.gpg")) {
        $count++;
        $dest = sprintf("upload-%d-of-%s", $count, $base);
    }

    $fp = fopen("$index", 'a');
    fprintf($fp, "%s\n  %s\n    %s\n\n", date('r'), "$dest.gpg", $description);
    fclose($fp);

    $dest = $dir . "/$dest";

    rename($source, $dest);
    encrypt_file($dest, array('dooglus@gmail.com', 'aml@worldbitcoinexchange.com'));
    @unlink($dest);

    echo "<p>File '$filename' was uploaded and encrypted successfully.</p>\n";

    return 1;
}

function handle_uploaded_identity_docs()
{
    global $is_logged_in;
?>
    <div class='content_box'>
    <h3>Upload Results</h3>
<?php
    $uploaded = 0;
    for ($i = 0; $i < ID_FILE_UPLOAD_SLOTS; $i++)
        $uploaded += upload_identity_doc($i);

    echo "<p>" . _("Documents uploaded") . ": $uploaded</p>\n";
    echo "</div>\n";

    if ($uploaded)
        email_tech(_("User Uploaded New Identity Documents"),
                   sprintf("%s\n\n%s",
                           sprintf(_("User %s uploaded %s new file(s)."),
                                   $is_logged_in, $uploaded),
                           sprintf("%s?page=docs&uid=%s", SITE_URL, $is_logged_in)));
}

function show_upload_documentation_form()
{
    global $is_logged_in, $is_verified;

    if ($is_verified) {
?>
    <div class='content_box'>
    <h3>Already Verified</h3>
    <p>
    Your account is already verified.  There is no need for you to upload any more documentation.  Thank you for putting up with this inconvenience.
    </p>
    </div>
<?php
    } else {
?>
    <div class='content_box'>
    <h3>Upload Personal Documentation</h3>
<?php
    $readme = ABSPATH . "/docs/$is_logged_in/00-README.txt";
    if (file_exists($readme)) {
        echo "<p>You have already uploaded the following:</p><br/><pre>\n";
        $fp = fopen($readme, 'r');
        while ($line = fgets($fp)) {
            $line = rtrim($line);
            // $line = substr($line, 35);
            echo "    $line\n";
        }
        echo "</pre>\n";
        echo "<p>The upload form is available below if you need to upload more.</p>\n";
    }
?>
    <p>
        <b>Please upload both of the following:
        <ul><li>a copy of an international ID document (a current driving license is sufficient) AND</li>
            <li>a copy of a recent utility bill (private) or corporate information (company)</li>
        </ul></b>
    </p>
    <p>
        All received documentation is immediately encrytped and held
        on a secure data store.
    </p>
    <p>
        We will not share your documents with any third party under any circumstance,
        except where legally obliged to do so.
    </p>
    <p>
        If you need to upload more than <?php echo post_max_size(); ?> of documents, please upload the documents separately.  There is a maximum of <?php echo post_max_size(); ?> upload per page.
    </p>

    <form action='' class='indent_form' method='post' enctype='multipart/form-data' id='foo'>
    <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
    <input type='hidden' name='upload_doc' value='true' />
<?php
    for ($i = 0; $i < ID_FILE_UPLOAD_SLOTS; $i++) {
        echo "    <label for='file$i'>File " . ($i+1) . ":</label><input type='file' id='file$i' name='file$i'>\n";
        echo "    <label for='description$i'>Description: </label><input style='width: 680px;' type='text' id='description$i' name='description$i'>\n";
        echo "    <br/>\n";
    }
?>
    <input type='submit' />
    </form>
    </div>
<?php
    }
}

if (isset($_POST['upload_doc'])) {
    handle_uploaded_identity_docs();
}

show_upload_documentation_form();

?>
