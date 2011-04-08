<?php

# try to get the config file location from a config file,
# in case virtual hosts want to share the code base.
$config_file = getenv('intersango_config');

# otherwise just open config.php on this directory
if (empty($config_file)) {
    $config_file = 'config.php';
}
require $config_file;

mysql_connect($db_server, $db_user, $db_pass) or die(mysql_error());
mysql_select_db($db_name) or die(mysql_error());

function connect_bitcoin()
{
     disable_errors_if_not_me();
     $bitcoin = new jsonRPCClient($btc_url);
     enable_errors();
     return $bitcoin;
}

function escapestr($str)
{
    return mysql_real_escape_string(strip_tags(htmlspecialchars($str)));
}
function do_query($query)
{
    $result = mysql_query($query) or die(mysql_error());
    return $result;
}
function has_results($result)
{
    if (mysql_num_rows($result) > 0)
        return true;
    else
        return false;
}
function get_row($result)
{
    $row = mysql_fetch_array($result, MYSQL_ASSOC);
    if (!$row)
        throw new Error('Ooops!', "Seems there's a missing value here.");
    return $row;
}

function numstr_to_internal($numstr)
{
    return bcmul($numstr, pow(10, 8), 0);
}
function internal_to_numstr($num, $precision=8)
{
    $repr = gmp_strval($num);
    $repr = bcdiv($repr, pow(10, 8), $precision);
    # now tidy output...
    # trim trailing 0s
    $repr = rtrim($repr, '0');
    # and a trailing . if it exists
    $repr = rtrim($repr, '.');
    return $repr;
}

function clean_sql_numstr($numstr)
{
    $numstr = rtrim($numstr, '0');
    $numstr = rtrim($numstr, '.');
    return $numstr;
}

?>

