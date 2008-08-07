<?php

// No problems to start
$problems = null;

// Enforce minimum version of PHP 5.2.3
if (preg_replace('/[a-z-]/i', '', phpversion()) < '5.2.3') {
    $problems .= "Error: sux0r requires PHP 5.2.3, or higher. \n";
}

// Check for mbstring
if (!extension_loaded('mbstring')) {
    $problems .= "Error: sux0r requires the mbstring extension, see: http://php.net/mbstring \n";
}

// Echo problems?
echo '<pre>';
if ($problems) echo $problems;
else echo 'Everything seems fine!';
echo '</pre>';

?>