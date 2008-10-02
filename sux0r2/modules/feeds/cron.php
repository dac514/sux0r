<?php

// Cron job to retrieve RSS feeds and put them in the database

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');
require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');

try {
    $rss = new suxRSS();
    $rss->cron();
}
catch (Exception $e) {
	echo $e->getMessage();
}

?>