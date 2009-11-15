<?php

// Enforce config
if (!isset($GLOBALS['CONFIG'])) {
    die("Something is wrong, can't define exception handler without configuration.");
}

// ----------------------------------------------------------------------------
// Default Exception Handler
// ----------------------------------------------------------------------------

// $e defined in index.php
$message = 'Error: ';
$message .= $e->getMessage() . "\n";
$message .= "File: " . $e->getFile() . "\n";
$message .= "Line: " . $e->getLine() . "\n\n";

if ($GLOBALS['CONFIG']['DEBUG'])
    $message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";

if (!headers_sent()) header('Content-Type: text/plain');
die($message);

?>