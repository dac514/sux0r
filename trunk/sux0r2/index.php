<?php

/**
* index
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
*
*/

require_once(dirname(__FILE__) . '/config.php'); // Configuration
require_once(dirname(__FILE__) . '/initialize.php'); // Initialization

// ---------------------------------------------------------------------------
// Prepare
// ---------------------------------------------------------------------------

// Defaults
$controller = 'home';
$action = 'default';
$params = array();

// Get controller & params
if (!empty($_GET['c'])) {
    $params = explode('/', $_GET['c']);
    $controller = array_shift($params);
    $action = array_shift($params);
}

// Pre-sanitize controller
$controller = mb_strtolower($controller);
if (!preg_match('/^(\w|\-)+$/', $controller) || !is_file(dirname(__FILE__) . "/modules/{$controller}/controller.php")) {
    if (!headers_sent()) header('HTTP/1.0 404 Not Found');
    echo suxFunct::getIncludeContents(dirname(__FILE__) . '/404.php');
    exit;
}

// Pre-sanitize action
$action = mb_strtolower($action);
if (!preg_match('/^(\w|\-)+$/', $action)) $action = 'default';

// Pre-sanitize params
foreach ($params as $key => $val) {
    if (!preg_match('/^(\w|\-)+$/', $val)) $params[$key] = null;
}

// ---------------------------------------------------------------------------
// Go!
// ---------------------------------------------------------------------------

try {
    include_once(dirname(__FILE__) . "/modules/{$controller}/controller.php");
    sux($action, $params);
}
catch (Exception $e) {
    require_once(dirname(__FILE__) . '/exception.php'); // Default exception handler
    exit;
}

// ---------------------------------------------------------------------------
// Breadcrumbs
// ---------------------------------------------------------------------------

if (!isset($_SESSION['breadcrumbs'])) $_SESSION['breadcrumbs'] = array();
array_unshift($_SESSION['breadcrumbs'], filter_var(trim(trim(isset($_GET['c']) ? $_GET['c'] : 'home'), '/'), FILTER_SANITIZE_URL));
$_SESSION['breadcrumbs'] = array_unique($_SESSION['breadcrumbs']);
$_SESSION['breadcrumbs'] = array_slice($_SESSION['breadcrumbs'], 0, 10); // maximum 10

// new dBug($_SESSION['breadcrumbs']);

?>