<?php

// Ajax
// Echo the content of a bayesian document

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');
require_once(dirname(__FILE__) . '/../../includes/suxNaiveBayesian.php');

if (filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $nb = new suxNaiveBayesian();
    $doc = $nb->getDocument($_GET['id']);
    if ($doc) {
        echo "<pre>{$doc['content']}</pre>\n";
    }
}

?>