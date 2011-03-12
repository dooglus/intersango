<?php
require '/var/db.intersango.inc';

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

