<?php

/**
* initialize
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

// Get rid of register_globals
if (ini_get('register_globals')) {
    foreach ($_REQUEST as $k => $v) {
        unset($GLOBALS[$k]);
    }
}

// Enforce config
if (!isset($GLOBALS['CONFIG'])) {
    die("Something is wrong, can't initialize without configuration.");
}

// Initialize suxDB
require_once($GLOBALS['CONFIG']['PATH'] . '/includes/suxDB.php');
suxDB::$dsn = $GLOBALS['CONFIG']['DSN'];

// Include suxFunct
require_once($GLOBALS['CONFIG']['PATH'] . '/includes/suxFunct.php');

// Sessions
ini_set('session.use_only_cookies', true);
session_start();

// Set utf-8
header('Content-Type: text/html;charset=utf-8');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
mb_language('uni');

// Avoid problems with arg_separator.output
ini_set('arg_separator.output', '&');

// Set the default timezone
date_default_timezone_set($GLOBALS['CONFIG']['TIMEZONE']);

// Get rid of magic quotes
if (get_magic_quotes_gpc() && (!ini_get('magic_quotes_sybase'))) {
    $in = array(&$_GET, &$_POST, &$_REQUEST, &$_COOKIE, &$_FILES);
    while (list($k,$v) = each($in)) {
        foreach ($v as $key => $val) {
            if (!is_array($val)) {
                $in[$k][$key] = stripslashes($val);
                continue;
            }
            $in[] =& $in[$k][$key];
        }
    }
    unset($in);
}

// Include suxUser
require_once($GLOBALS['CONFIG']['PATH'] . '/includes/suxUser.php');

// Validate user $_SESSION
if (isset($_SESSION['users_id']) || isset($_SESSION['nickname'])) {
    $u = new suxUser();
    $u->loginCheck(suxFunct::makeUrl('/home'));
}
unset($u);

?>