<?php
// require_once '/var/db.intersango.inc';
require_once '/home/worldbit/db.intersango.inc';
require_once 'htdocs/config.php';

function escapestr($str)
{
    return mysql_real_escape_string(strip_tags(htmlspecialchars($str)));
}
function do_query($query)
{
    // echo "query: $query<br/>\n";
    $result = mysql_query($query);
    if (!$result)
        throw new Error(_("MySQL Error"), mysql_error());
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

function internal_to_numstr($num, $precision=-1, $round = true)
{
    if ($precision == -1) {
        $precision = 8;
        $tidy = true;
    } else
        $tidy = false;

    if (!is_string($num) && !is_resource($num))
        throw new Error('Coding error!', "internal_to_numstr argument has type '" . gettype($num) . "'");
    $repr = gmp_strval($num);
    if ($round)
        if ($repr > 0)
            $repr = bcadd($repr, pow(10, (8 - $precision)) / 2);
        else
            $repr = bcsub($repr, pow(10, (8 - $precision)) / 2);
    $repr = bcdiv($repr, pow(10, 8), $precision);

    // now tidy output...
    if ($tidy)
        return clean_sql_numstr($repr);
    return sprintf("%.{$precision}f", $repr);
}

function clean_sql_numstr($numstr)
{
    if (strpos($numstr, '.') !== false) {
        $numstr = rtrim($numstr, '0');
        $numstr = rtrim($numstr, '.');
    }
    return $numstr;
}

do_query("set time_zone = '".TIMEZONE."'");

?>
