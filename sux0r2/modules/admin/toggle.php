<?php

// Ajax
// Toggle subscription to a bookmark

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');

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
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT) || $_POST['id'] < 1) failure('Invalid bookmark id');

$id = $_POST['id'];

// ---------------------------------------------------------------------------
// Secondary error checking
// ---------------------------------------------------------------------------

$u = new suxUser();
if (!$u->isRoot()) failure('Not admin');

// ---------------------------------------------------------------------------
// Go
// ---------------------------------------------------------------------------

$image = 'lock2.gif';
$flag = $u->toggleLogPrivateFlag($id);
if ($flag) $image = 'lock1.gif';

echo trim($image);
exit;

?>
