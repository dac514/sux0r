<?php

// Ajax
// Toggle subscription to a bookmark

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../initialize.php');

// ---------------------------------------------------------------------------
// Ajax Failure
// ---------------------------------------------------------------------------

function failure($msg = null) {
    if (!headers_sent()) header("HTTP/1.0 500 Internal Server Error");
    if ($msg) echo "Something went wrong: \n\n $msg";
    die();
}

// ---------------------------------------------------------------------------
// Error checking
// ---------------------------------------------------------------------------

if (!isset($_SESSION['users_id'])) failure('Invalid user id');
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT) || $_POST['id'] < 1) failure('Invalid id');

$id = $_POST['id'];

// ---------------------------------------------------------------------------
// Secondary error checking
// ---------------------------------------------------------------------------

$user = new suxUser();
$log = new suxLog();
if (!$user->isRoot()) failure('Not admin');

// ---------------------------------------------------------------------------
// Go
// ---------------------------------------------------------------------------

try {
    $image = 'lock2.gif';
    $flag = $log->toggleLogPrivateFlag($id);
    if ($flag) $image = 'lock1.gif';

    // Log, private
    $log->write($_SESSION['users_id'], "sux0r::admin::toggle() users_log_id: $id", 1);
}
catch (Exception $e) {
    $message = $e->getMessage();
    $message .= "File: " . $e->getFile() . "\n";
    $message .= "Line: " . $e->getLine() . "\n\n";
    failure($message);
}

echo trim($image);
exit;

