<?php

/**
* index
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/config.php'); // Configuration

try {

    require_once($GLOBALS['CONFIG']['PATH'] . '/initialize.php'); // Initialization

    // ------------------------------------------------------------------------
    // Prepare
    // ------------------------------------------------------------------------

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

    // Sanity check controller
    $controller = mb_strtolower($controller);

    if ($controller == 'banned') {
        // Banned
        $controller = 'globals';
        include_once($GLOBALS['CONFIG']['PATH'] . "/modules/globals/controller.php");
        sux('banned');
        exit;
    }
    elseif (!preg_match('/^(\w|\-)+$/', $controller) || !is_file($GLOBALS['CONFIG']['PATH'] . "/modules/{$controller}/controller.php")) {
        // 404 Not Found
        $controller = 'globals';
        if (!headers_sent()) header('HTTP/1.0 404 Not Found');
        include_once($GLOBALS['CONFIG']['PATH'] . "/modules/globals/controller.php");
        sux('e404');
        exit;
    }

    // Sanity check action
    $action = mb_strtolower($action);
    if (!preg_match('/^(\w|\-)+$/', $action)) $action = 'default';

    // Sanity check params
    foreach ($params as $key => $val) {
        if (!preg_match('/^(\w|\-)+$/', $val)) $params[$key] = null;
    }

    // -----------------------------------------------------------------------
    // Go!
    // ------------------------------------------------------------------------

    include_once($GLOBALS['CONFIG']['PATH'] . "/modules/{$controller}/controller.php");
    sux($action, $params);

}
catch (Exception $e) {
    require_once($GLOBALS['CONFIG']['PATH'] . '/exception.php'); // Default exception handler
    exit;
}

// ---------------------------------------------------------------------------
// Breadcrumbs
// ---------------------------------------------------------------------------

$crumb = filter_var(trim(trim(isset($_GET['c']) ? $_GET['c'] : 'home'), '/'), FILTER_SANITIZE_URL);
if (isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) && $_GET['page'] > 0) {
    $crumb .= $GLOBALS['CONFIG']['CLEAN_URL'] ? '?' : '&';
    $crumb .= "page={$_GET['page']}";
}

if (!isset($_SESSION['breadcrumbs'])) $_SESSION['breadcrumbs'] = array();
array_unshift($_SESSION['breadcrumbs'], $crumb);
$_SESSION['breadcrumbs'] = array_unique($_SESSION['breadcrumbs']);
$_SESSION['breadcrumbs'] = array_slice($_SESSION['breadcrumbs'], 0, 10); // maximum 10
$_SESSION['birdfeed'] = 0; // Reset, used to prevent infinite redirects

// new dBug($_SESSION);

?>