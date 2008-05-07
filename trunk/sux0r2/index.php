<?php

require_once(dirname(__FILE__) . '/config.php');
require_once (dirname(__FILE__) . '/initialize.php');

// ---------------------------------------------------------------------------
// Sanity check
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
if (!preg_match('/^(\w|\-)+$/', $controller)) $controller = 'home';
if (!is_file(dirname(__FILE__) . "/modules/{$controller}/controller.php")) $controller = 'home';

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

    // Load file
    if (!include_once(dirname(__FILE__) . "/modules/{$controller}/controller.php")) {
        throw(new Exception('Failed to initialize controller'));
    }

    sux($action, $params);

}
catch (Exception $e) {

    echo 'Something went horribly wrong...';
    new dBug($controller);
    new dBug($params);

    $message = "index Error: \n";
    $message .= $e->getMessage() . "\n";
    $message .= "File: " . $e->getFile() . "\n";
    $message .= "Line: " . $e->getLine() . "\n\n";
    $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
    die("<pre>{$message}</pre>");

}


?>