<?php
function enable_errors()
{
    error_reporting(E_ALL|E_STRICT);
    ini_set('display_errors', '1');
}
function disable_errors_if_not_me()
{
    if (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] != "127.0.0.1") {
        error_reporting(-1);
        ini_set('display_errors', '0');
    }
}

class Problem extends Exception
{
    # PHP sucks!
    public function __construct($title, $message)
    {
        parent::__construct($message);
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
class Error extends Problem
{
}

function beginlog()
{
    openlog("intersango", LOG_PID, LOG_LOCAL0);
}
function endlog()
{
    closelog();
}

class SEVERITY
{
    const PROBLEM = 0;
    const ERROR = 1;
    const BAD_PAGE = 2;
}

function report($message, $severity)
{
    $uid = '';
    if (isset($_SESSION['uid']))
        $uid = $_SESSION['uid'];
    $time = time();
    $message = "$uid $time: $message";

    switch ($severity) {
        case SEVERITY::PROBLEM:
            $filename = "/var/tmp/problem-reports.log";
            break;

        case SEVERITY::ERROR:
            $filename = "/var/tmp/error-reports.log";
            break;

        case SEVERITY::BAD_PAGE:
            $filename = "/var/tmp/bad-page-reports.log";
            break;

        default:
            report("Invalid report for $message of $severity!", SEVERITY::ERROR);
            break;
    }

    error_log("$message\n", 3, $filename);
    beginlog();
    syslog(LOG_CRIT, $message);
    endlog();
    # do this last because it's the most risky operation, and we at least want some logs first.
    if ($severity == SEVERITY::ERROR) {
        #echo exec("echo 'A fatal error has occured. Time is now $time.' | mutt -s INTERSANGO_ERROR genjix@gmail.com -a $filename");
    }
}
function log_badpage($page)
{
    report($page, SEVERITY::BAD_PAGE);
    throw new Problem('Invisible', 'You have gone to a wrong page. Go somewhere else.');
}
function report_exception($e, $severity)
{
    $title = $e->getTitle();
    $message = $e->getMessage();
    report("Exception:\n==== $title ====\n$message\n================", $severity);
}

function reporting_error_handler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        # This error code is not included in error_reporting
        return;
    }
    switch ($errno) {
    case E_USER_ERROR:
        report("[$errno] $errstr $errline in $errfile", SEVERITY::ERROR);
        exit(1);
        break;

    case E_USER_WARNING:
        report("WARNING: [$errno] $errstr", SEVERITY::ERROR);
        break;

    case E_USER_NOTICE:
        report("NOTICE: [$errno] $errstr", SEVERITY::ERROR);
        break;

    default:
        report("UNKNOWN: [$errno] $errstr", SEVERITY::ERROR);
        break;
    }
    # Don't execute PHP internal error handler
    return false;
}                                
function reporting_shutdown() { 
    $error = error_get_last();
    if ($error != NULL) {
        $info = "[SHUTDOWN] file:".$error['file']." | ln:".$error['line']." | msg:".$error['message'] .PHP_EOL;
        report($info, SEVERITY::ERROR);
    } 
}

set_error_handler("reporting_error_handler");
register_shutdown_function("reporting_shutdown");

