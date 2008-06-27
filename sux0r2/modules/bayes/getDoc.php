<?php

// Ajax
// Echo the content of a bayesian document

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once(dirname(__FILE__) . '/../../initialize.php');
    require_once(dirname(__FILE__) . '/../../includes/suxNaiveBayesian.php');
    require_once(dirname(__FILE__) . '/../../includes/suxLink.php');

    $nb = new suxNaiveBayesian();
    $doc = $nb->getDocument($_GET['id']);
    if ($doc) {

        $text = suxFunct::gtext('bayes');

        $tmp = null;
        $link = new suxLink();
        foreach ($link->getLinkTables('bayes') as $table) {
            $links = $link->getLinks($table, 'bayes_documents', $_GET['id']);
            if($links && count($links)) {

                $table = str_replace('link_', '', $table);
                $table = str_replace('bayes', '', $table);
                $table = str_replace('_', '', $table);

                $tmp .= "[ {$text['to']} {$table}_id -&gt; ";
                foreach ($links as $val) $tmp .= " $val,";
                $tmp = rtrim($tmp, ', ');
                $tmp .= ' ]';
            }
        }

        echo '<em>bayes_document_id: ' . $_GET['id'] . '</em><br />';

        if ($tmp) {
            echo "<em><strong>{$text['is_linked']}</strong></em> ";
            echo $tmp;
        }
        else {
            echo "<em><strong>{$text['is_not_linked']}</strong></em> ";
        }
        echo "\n<div style='border-bottom: 1px solid #ccc; margin-top:4px;'></div>\n";
        echo "<pre>";
        echo trim($doc['body']);
        echo "</pre>\n";
    }

}

?>