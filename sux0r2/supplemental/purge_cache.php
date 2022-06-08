<?php

require_once(__DIR__  . '/../includes/suxFunct.php'); // Configuration

// Cache dirs to delete
$cache = array();
$cache[] = realpath(__DIR__ . '/../temporary/cache/');
$cache[] = realpath(__DIR__ . '/../temporary/rss_cache/');
$cache[] = realpath(__DIR__ . '/../temporary/templates_c/');

// Go!
foreach($cache as $dir) {
    suxFunct::obliterateDir($dir);
}

