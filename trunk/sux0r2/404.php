<?php

require_once(dirname(__FILE__) . '/config.php'); // Configuration

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title><?php echo $GLOBALS['CONFIG']['TITLE']; ?> - 404 Not Found</title>
    <style>
    body  { font-family: Arial, Helvetica, sans-serif; background-color: #ffffff; width: 640px; }
    a:link, a:visited, a:active { font-weight: bold; color: #000000; text-decoration: underline; }
    a:hover { color: #ffffff; background: #000000; }
    </style>
</head>
<body>

    <h1>Oops, Page Not Found (Error 404)</h1>

    <p>For some reason (mis-typed URL, faulty referral from
    another site, out-of-date search engine listing or we simply deleted a
    file) the page you were after is not here.</p>

    <p><a href="<?php echo $GLOBALS['CONFIG']['URL']; ?>">Click here to continue &raquo;</a></p>

    <p><img src="<?php echo $GLOBALS['CONFIG']['URL']; ?>/media/sux0r/assets/sewerhorse.jpg" alt="Sewer Horse Is Watching You" /></p>

</body>
</html>