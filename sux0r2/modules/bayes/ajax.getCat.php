<?php

// Ajax
// Echo the categorization of a document

// ---------------------------------------------------------------------------
// Require
// ---------------------------------------------------------------------------

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/../../initialize.php');

$text = suxFunct::gtext('bayes');

// ---------------------------------------------------------------------------
// Error checking
// ---------------------------------------------------------------------------

if (!filter_var(@$_POST['id'], FILTER_VALIDATE_INT)) {
    echo "<p>{$text['error']}: {$text['form_error_1']}</p>";
    exit;
}

if (!trim(@$_POST['document'])) {
    echo "<p>{$text['error']}: {$text['form_error_7']}</p>";
    exit;
}

// ---------------------------------------------------------------------------
// Generate HTML
// ---------------------------------------------------------------------------

$nb = new suxNaiveBayesian();
$scores = $nb->categorize($_POST['document'], $_POST['id']);

$html = '<p><table border="1">';
$html .= '<thead><tr><th>' . $text['categories'] . '</th><th>' . $text['scores'] . '</th></tr></thead><tbody>'. "\n";
foreach ($scores as $k => $v) {
    $html .= "<tr><td>{$v['category']}</td><td>" . round($v['score'] * 100, 2) . " %</td></tr>\n";
}
$html .= '</tbody></table></p>' . "\n";
$html .= '<p><em>' . $text['categorized_on'] . ' : ' . date('D M j, G:i:s') . '</em></p>';

echo $html;

