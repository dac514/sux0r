<?php

// Cron job to retrieve RSS feeds and put them in the database

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../initialize.php');

try {
    $rss = new suxRSS();
    $rss->cron();
}
catch (Exception $e) {
	echo $e->getMessage() , "\n";
    echo "File: " , $e->getFile() , "\n";
    echo "Line: " , $e->getLine() , "\n\n";    
}

