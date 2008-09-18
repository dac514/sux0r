<?php

require_once(dirname(__FILE__) . '/config.php'); // Configuration
require_once(dirname(__FILE__) . '/initialize.php'); // Initialization
$text = suxFunct::gtext(); // Language

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title>404 Not Found</title>
    <style>
    body  { font-family: Arial, Helvetica, sans-serif; background-color: #ffffff; }
    a:link, a:visited, a:active { font-weight: bold; color: #000000; text-decoration: underline; }
    a:hover { color: #ffffff; background: #000000; }
    p { width: 640px; }
    </style>
</head>
<body>
    <h1><?php echo $text['404_h1']; ?></h1>
    <p><?php echo $text['404_p1']; ?></p>
    <p><a href="<?php echo $GLOBALS['CONFIG']['URL']; ?>"><?php echo $text['404_continue']; ?> &raquo;</a></p>
    <p><img src="<?php echo $GLOBALS['CONFIG']['URL']; ?>/media/sux0r/assets/sewerhorse.jpg" alt="Sewer Horse Is Watching You" /></p>
</body>
</html>