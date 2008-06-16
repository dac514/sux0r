<?php

// Enforce config
if (!isset($GLOBALS['CONFIG'])) {
    die("Something is wrong, can't define exception handler without configuration.");
}

// ----------------------------------------------------------------------------
// Default Exception Handler
// ----------------------------------------------------------------------------

$message = 'Error: ';
$message .= $e->getMessage() . "\n";
$message .= "File: " . $e->getFile() . "\n";
$message .= "Line: " . $e->getLine() . "\n\n";
$message .= "Backtrace: \n" . print_r($e->getTrace(), true) . "\n\n";
die("<pre>{$message}</pre>");

?>