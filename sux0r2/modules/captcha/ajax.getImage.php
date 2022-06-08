<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED); // Wimpy mode

ini_set('session.use_only_cookies', true);
session_start();

require_once(dirname(__FILE__) . '/../../includes/symbionts/securimage/securimage.php');

$image = new Securimage();

// Set some variables
$image->use_wordlist = false;
$image->perturbation = 0.05;
$image->ttf_file =  realpath(dirname(__FILE__) . '/../../includes/symbionts/securimage/AHGBold.ttf');

$image->show();

?>