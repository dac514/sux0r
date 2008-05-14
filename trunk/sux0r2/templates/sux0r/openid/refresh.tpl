<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{$r->title}</title>
    <meta content="text/html;charset=utf-8" http-equiv="content-type" />
    <meta http-equiv="refresh" content="0;url={$r->text.url}">
    <meta name="robots" content="noindex,nofollow" />
</head>
<body>

    {* This template is a special case. We do not want to pollute it with
    unecessary style sheets and whatnot, so the entire XHTML document is
    represented and displayed as opposed to assembled *}

    <p>{$r->text.redirect} <a href="{$r->text.url}">{$r->text.url}</a></p>

</body>
</html>