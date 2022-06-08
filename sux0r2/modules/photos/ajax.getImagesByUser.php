<?php

// Ajax
// TinyMCE external image list url
// http://wiki.moxiecode.com/index.php/TinyMCE:Configuration/external_image_list_url

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');

if (!isset($_SESSION['users_id'])) exit;

$photo = new suxPhoto();
$images = $photo->getPhotosByUser(null, 0, $_SESSION['users_id']);

$output = 'var tinyMCEImageList = new Array(';
if ($images) foreach ($images as $image) {

    $output .= "\n"
    . '["'
    . utf8_encode($image['image'])
    . '", "'
    . utf8_encode("{$GLOBALS['CONFIG']['URL']}/data/photos/" . suxPhoto::t2fImage($image['image']))
    . '"],';

}
if ($images) $output = substr($output, 0, -1); // remove last comma
$output .= "\n" . ');';

header('Content-type: text/javascript'); // Make output a real JavaScript file
echo $output;

