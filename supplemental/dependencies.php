<?php

require_once(dirname(__FILE__)  . '/../config.php'); // Configuration

// ---------------------------------------------------------------------------
// Check for problems
// ---------------------------------------------------------------------------

// No problems to start
$prob = null;
$continue = false;

// Enforce minimum version of PHP 5.2.3
if (preg_replace('/[a-z-]/i', '', phpversion()) < '5.2.3') {
    $prob .= "Error: sux0r requires PHP 5.2.3, or higher. see: http://www.gophp5.org/ \n";
}

// Check for mbstring
if (!extension_loaded('mbstring')) {
    $prob .= "Error: sux0r requires the mbstring extension, see: http://php.net/mbstring \n";
}

// Check for PDO
if (!extension_loaded('pdo_mysql') && !extension_loaded('pdo_pgsql')) {
    $prob .= "Error: sux0r requires the PDO extension for either MySQL or PostgreSQL, see: http://php.net/pdo \n";
}

// Problems?
if (!$prob) {
    $prob = 'Ok!';
    $continue = true;
}

// ---------------------------------------------------------------------------
// Inline, horrible inline
// ---------------------------------------------------------------------------

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title><?php echo $GLOBALS['CONFIG']['TITLE']; ?> - Dependencies</title>
    <style type="text/css">
    body  { font-family: Arial, Helvetica, sans-serif; background-color: #ffffff; }
    a:link, a:visited, a:active { font-weight: bold; color: #000000; text-decoration: underline; }
    a:hover { color: #ffffff; background: #000000; }
    </style>
</head>
<body>

    <pre><?php echo $prob; ?></pre>

    <?php if ($continue) { ?>
    <p><a href="<?php echo $GLOBALS['CONFIG']['URL']; ?>"><?php echo $GLOBALS['CONFIG']['TITLE']; ?> &raquo;</a></p>
    <?php } ?>

</body>
</html>