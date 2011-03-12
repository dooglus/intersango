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
    $slen = strlen($numstr);
    $dotpos = strrpos($numstr, '.');
    if ($dotpos === false) {
        $num = gmp_init($numstr);
        $num = gmp_mul($numstr, pow(10, 8));
    }
    else {
        # remove the dot from the string
        $significand = substr($numstr, 0, $dotpos);
        $decimals = substr($numstr, $dotpos + 1, $slen);
        $num_decimals = strlen($decimals);
        if ($num_decimals > 8) {
            $decimals = substr($decimals, 0, 8);
            $num_decimals = strlen($decimals);
        }        
        $numstr = $significand . $decimals;
        # GMP doesn't like leading 0s
        $numstr = ltrim($numstr, '0');
        $num = gmp_init($numstr);
        $num = gmp_mul($numstr, pow(10, 8 - $num_decimals));
    }
    return $num;
}

function internal_to_numstr($internal)
{
    $inrepr = gmp_strval($internal);
    $inrlen = strlen($inrepr);
    if (gmp_cmp($internal, pow(10, 8)) < 0) {
        $numstr = '0.' . str_repeat('0', 8 - $inrlen) . $inrepr;
        $numstr = rtrim($numstr, '0');
    }
    else {
        $dotpos = $inrlen - 8;
        $decimals = substr($inrepr, $dotpos, 8);
        $decimals = rtrim($decimals, '0');
        $significand = substr($inrepr, 0, $dotpos);
        $numstr = $significand . '.' . $decimals;
    }
    # if there's no 0's after the . then it will clip it
    # otherwise the decimal remains
    $numstr = rtrim($numstr, '.');
    return $numstr;
}

function clean_sql_numstr($numstr)
{
    $numstr = rtrim($numstr, '0');
    $numstr = rtrim($numstr, '.');
    return $numstr;
}

?>

