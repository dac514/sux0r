<?php

// Ajax
// Echo the categorization of a document

// ---------------------------------------------------------------------------
// Sanitize
// ---------------------------------------------------------------------------

if (!filter_var(@$_POST['id'], FILTER_VALIDATE_INT)) {
    echo '<p>Error: Vector cannot be empty</p>';
    exit;
}

if (!trim(@$_POST['document'])) {
    echo '<p>Error: Document cannot be empty</p>';
    exit;
}

// ---------------------------------------------------------------------------
// Require
// ---------------------------------------------------------------------------

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/../../initialize.php');
require_once(dirname(__FILE__) . '/../../includes/suxNaiveBayesian.php');

// ---------------------------------------------------------------------------
// Generate HTML
// ---------------------------------------------------------------------------

$nb = new suxNaiveBayesian();
$scores = $nb->categorize($_POST['document'], $_POST['id']);

$html = '<p><table border="1">';
$html .= '<thead><tr><th>Categories</th><th>Scores</th></tr></thead><tbody>'. "\n";
foreach ($scores as $k => $v) {
    $html .= "<tr><td>{$k}</td><td>" . round($v*100, 2) . " %</td></tr>\n";
}
$html .= '</tbody></table></p>' . "\n";
$html .= '<p><em>Categorized on: ' . date('D M j, G:i:s') . '</em></p>';

echo $html;

?>