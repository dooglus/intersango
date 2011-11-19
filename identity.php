<?php

function upload_identity_doc($num)
{
    global $is_logged_in;

    $file = "file$num";
    $description = post("description$num");

    if (!isset($_FILES[$file]))
        return false;

    $info = $_FILES[$file];
    if ($info['error'])
        return false;

    $filename = cleanup_string(basename($info['name']));
    $type = $info['type'];
    $source = $info['tmp_name'];
    $size = $info['size'];

    $dir = DOCDIR . "/$is_logged_in";
    @mkdir($dir, 0755);
    $dest = $dir . "/$filename";
    $index = $dir . "/00-README.txt";

    $fp = fopen("$index", 'a');
    fprintf($fp, "%-35s %-40s %s\n", date('r'), $filename, $description);
    fclose($fp);

    @unlink($dest);
    @unlink("$dest.gpg");
    rename($source, $dest);
    encrypt_file($dest, array('dooglus@gmail.com', 'aml@worldbitcoinexchange.com'));
    @unlink($dest);

    echo "<p>File '$filename' was uploaded and encrypted successfully.</p>\n";
}

function handle_uploaded_identity_docs()
{
?>
    <div class='content_box'>
    <h3>Upload Results</h3>
<?php
    upload_identity_doc(1);
    upload_identity_doc(2);
?>
    </div>
<?php
}

function show_upload_documentation_form()
{
    global $is_logged_in;

    if (get_verified_for_user($is_logged_in)) {
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
    <p>
        Please upload a copy of an international ID document plus a copy of a recent utility bill (private) or corporate information (company) using the form below.  All uploaded documents will be encrypted and held in a secure location.
    </p>
    <p>
        Alternatively you may email your documents to <a href="mailto:AML@worldbitcoinexchange.com">AML@worldbitcoinexchange.com</a>.  If you wish to use <a target="_blank" href="http://www.gnupg.org/">GPG encryption</a>, our key is <a target="_blank" href="http://pgp.mit.edu:11371/pks/lookup?op=vindex&search=0x7C5A1FADF88105BD">F88105BD</a> with fingerprint C0BF 6C02 E06D AA59 15D8  B982 7C5A 1FAD F881 05BD.
    </p>

    <form action='' class='indent_form' method='post' enctype='multipart/form-data' id='foo'>
    <input type='hidden' name='csrf_token' value="<?php echo $_SESSION['csrf_token']; ?>" />
    <input type='hidden' name='upload_doc' value='true' />
    <label for='file1'>File:</label><input type='file' id='file1' name='file1'>
    <label for='description1'>Description: </label><input style='width: 680px;' type='text' id='description1' name='description1'>
    <br/>
    <label for='file2'>File:</label><input type='file' id='file2' name='file2'>
    <label for='description2'>Description: </label><input style='width: 680px;' type='text' id='description2' name='description2'>
    <br/>
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
