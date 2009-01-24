<?php

/**
* initialize
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
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