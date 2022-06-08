<?php

// Ajax
// Toggle subscription to feed

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
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT) || $_POST['id'] < 1) failure('Invalid feed id');

$id = $_POST['id'];

// ---------------------------------------------------------------------------
// Secondary error checking
// ---------------------------------------------------------------------------

$feed = new suxRSS();
if (!$feed->getFeedByID($id)) failure('Invalid feed');

// ---------------------------------------------------------------------------
// Go
// ---------------------------------------------------------------------------

$module = 'feeds';
$link = 'link__rss_feeds__users';
$col = 'rss_feeds';

// Get image names from template config
$tpl = new suxTemplate($module);
$tpl->configLoad('my.conf', $module);
$image = $tpl->getConfigVars('imgUnsubscribed');

$db = suxDB::get();
$query = "SELECT COUNT(*) FROM {$link} WHERE {$col}_id = ? AND users_id = ? ";
$st = $db->prepare($query);
$st->execute(array($id, $_SESSION['users_id']));

if ($st->fetchColumn() > 0) {
    // Delete
    $query = "DELETE FROM {$link} WHERE {$col}_id = ? AND users_id = ? ";
    $st = $db->prepare($query);
    $st->execute(array($id, $_SESSION['users_id']));
}
else {
    // Insert
    $suxLink = new suxLink();
    $suxLink->saveLink($link, 'users', $_SESSION['users_id'], $col, $id);
    $image = $tpl->getConfigVars('imgSubscribed');
}

// Log
$log = new suxLog();
$log->write($_SESSION['users_id'], "sux0r::feeds::toggle() bookmarks_id: {$id}", 1); // Private

// ---------------------------------------------------------------------------
// Clear template caches
// ---------------------------------------------------------------------------

$tpl->clearCache(null, "{$_SESSION['nickname']}"); // clear all caches with "nickname" as the first cache_id group

// ---------------------------------------------------------------------------
// Return image string to Ajax request
// ---------------------------------------------------------------------------

echo trim($image);
exit;

