<?php

require_once(dirname(__FILE__)  . '/../includes/suxFunct.php'); // Configuration

// Cache dirs to delete
$cache[] = realpath(dirname(__FILE__) . '/../temporary/cache/');
$cache[] = realpath(dirname(__FILE__) . '/../temporary/rss_cache/');

// Go!
foreach($cache as $dir) {
    suxFunct::obliterateDir($dir);  
}

?>
