<?php

// Ajax
// Echo the content of a bayesian document

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once(dirname(__FILE__) . '/../../initialize.php');
    require_once(dirname(__FILE__) . '/../../includes/suxNaiveBayesian.php');

    $nb = new suxNaiveBayesian();
    $doc = $nb->getDocument($_GET['id']);
    if ($doc) {
        echo "<pre>{$doc['body']}</pre>\n";
    }

}

?>