<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); // Wimpy mode

ini_set('session.use_only_cookies', true);
session_start();

require_once(dirname(__FILE__) . '/../../includes/symbionts/securimage/securimage.php');

$image = new Securimage();

// Set some variables
$image->use_wordlist = false;
$image->gd_font_file = realpath(dirname(__FILE__) . '/../../includes/symbionts/securimage/gdfonts/bublebath.gdf');
$image->ttf_file =  realpath(dirname(__FILE__) . '/../../includes/symbionts/securimage/elephant.ttf');

$image->show();

// Use our own session variable
$_SESSION['captcha'] = $image->getCode();

?>