<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); // Wimpy mode

ini_set('session.use_only_cookies', true);
session_start();

require_once(dirname(__FILE__) . '/../../includes/symbionts/securimage/securimage.php');

$image = new Securimage();

// Set some variables
$image->use_wordlist = false;
$image->audio_path =  realpath(dirname(__FILE__) . '/../../includes/symbionts/securimage/audio') . '/'; // Force trailing slash

header('Content-type: audio/x-wav');
header('Content-Disposition: attachment; filename="securimage.wav"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Expires: Sun, 1 Jan 2000 12:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');

echo $image->getAudibleCode();

// Use our own session variable
$_SESSION['captcha'] = $image->getCode();

?>