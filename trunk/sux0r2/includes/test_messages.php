<?php

require_once '../config.php';
require_once '../initialize.php';
include_once 'suxThreadedMessages.php';

$msg = new suxThreadedMessages();

$body = '
<div onmouseover="alert(\'hi\');" style="width:800px; border: 1px solid red;" >
<h1>this is a test of the title (edit 2)</h1>
<p><img src="media/logo.jpg" alt=" " hspace="5" vspace="5" width="250" height="48" align="right" /> TinyMCE is &agrave; platform ind&eacute;pendent web based Javascript HTML <strong>WYSIWYG</strong> editor c&ocirc;ntrol released as Open Source under LGPL by Moxiecode Systems AB. It has the ability to &ccedil;onvert HTML TEXTAREA fields or other HTML elements to editor instances.
TinyMCE is very easy to integrate into other <span style="font-size: large;">Content Management Systems. </span></p>
<p>We recommend <a href="http://www.getfirefox.com" target="_blank">Firefox ãã¯ãã¯</a> and <a href="http://www.google.com" target="_blank">Google</a></p>
</div>
';

$m['title'] = 'Test';
$m['body'] = $body;
// $m['published_on'] = date('c');

// $id = $msg->saveMessage('1', $m);
// $id =  $msg->saveMessage('1', $m, 2);
// $id =  $msg->editMessage(1, 1, $m);

echo $id;


?>