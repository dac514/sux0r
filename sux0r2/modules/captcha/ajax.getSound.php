<?php

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING ^ E_DEPRECATED); // Wimpy mode

ini_set('session.use_only_cookies', true);
session_start();

require_once(__DIR__ . '/../../includes/symbionts/securimage/securimage.php');

$image = new Securimage();

// Set some variables
$image->use_wordlist = true;
$image->audio_path =  realpath(__DIR__ . '/../../includes/symbionts/securimage/audio/en') . '/'; // Force trailing slash

$image->outputAudioFile();

