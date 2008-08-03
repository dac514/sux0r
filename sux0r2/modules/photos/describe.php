<?php

// Ajax
// Describe a photo

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');

// ---------------------------------------------------------------------------
// Error checking
// ---------------------------------------------------------------------------

if (!isset($_SESSION['users_id'])) exit;
if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT)) exit;
if (!isset($_POST['description'])) exit;

// ---------------------------------------------------------------------------
// Secondary error checking
// ---------------------------------------------------------------------------

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');

$user = new suxUser();
$photo = new suxPhoto();

// Verify if user is allowed to edit this photo.
if (!$photo->isPhotoOwner($_POST['id'], $_SESSION['users_id'])) exit;

$clean = array(
    'id' => $_POST['id'],
    'description' => $_POST['description'],
    );

try {
    $photo->savePhoto($_SESSION['users_id'], $clean);
    $tmp = $photo->getPhoto($clean['id']);
    if ($tmp['description']) echo $tmp['description'];
    else echo 'Click me to edit this nice long text.'; // TODO: Translate
}
catch (Exception $e) {
    echo $e->getMessage();
}

// ---------------------------------------------------------------------------
// Clear template caches
// ---------------------------------------------------------------------------

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
$tpl = new suxTemplate('photos');
$tpl->clear_cache(null, "view|{$clean['id']}"); // clear all caches with "view|id"

?>