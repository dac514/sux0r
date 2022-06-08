<?php

// Ajax
// Describe a photo

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');

// ---------------------------------------------------------------------------
// Pre-processing
// ---------------------------------------------------------------------------

// Remove 'editme' from id
$_POST['id'] = str_replace('editme', '', $_POST['id']);

// ---------------------------------------------------------------------------
// Error checking
// ---------------------------------------------------------------------------

if (!isset($_SESSION['users_id'])) exit;
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) exit;
if (!isset($_POST['description'])) exit;

// ---------------------------------------------------------------------------
// Secondary error checking
// ---------------------------------------------------------------------------

$log = new suxLog();
$photo = new suxPhoto();

$text = suxFunct::gtext('photos');

// Verify if user is allowed to edit this photo.
if (!$photo->isPhotoOwner($_POST['id'], $_SESSION['users_id'])) exit;

$clean = array(
    'id' => $_POST['id'],
    'description' => $_POST['description'],
    );

try {
    $photo->savePhoto($_SESSION['users_id'], $clean);
    $tmp = $photo->getPhotoByID($clean['id']);
    if ($tmp['description']) echo $tmp['description'];
    else echo $text['clickme'];

    $log->write($_SESSION['users_id'], "sux0r::photos::describe() photos_id: {$clean['id']}", 1); // Private

}
catch (Exception $e) {
    echo $e->getMessage();
}

// ---------------------------------------------------------------------------
// Clear template caches
// ---------------------------------------------------------------------------

$tpl = new suxTemplate('photos');
$tpl->clearCache(null, $_SESSION['nickname']); // clear all user caches

