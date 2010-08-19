<?php

// Ajax
// Lament to the log

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');

// ---------------------------------------------------------------------------
// Error checking
// ---------------------------------------------------------------------------

if (!isset($_SESSION['users_id'])) die();
if (empty($_POST['lament'])) die();

$lament = strip_tags($_POST['lament']);
$lament = trim($lament);
$lament = substr($lament, 0, 500);

if (!$lament) die();

// ---------------------------------------------------------------------------
// Go
// ---------------------------------------------------------------------------

$log = new suxLog();
$log->write($_SESSION['users_id'], $lament);

// ---------------------------------------------------------------------------
// Clear template caches
// ---------------------------------------------------------------------------

$tpl = new suxTemplate('user');
$tpl->clear_cache('profile.tpl', "{$_SESSION['nickname']}|{$_SESSION['nickname']}");

echo $lament;

?>
