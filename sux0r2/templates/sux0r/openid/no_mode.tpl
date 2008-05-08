<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <title>suxOpenID</title>
    <meta content="text/html; charset=utf-8" http-equiv="content-type" />
    <meta name="robots" content="noindex,nofollow" />
</head>
<body>

    <p>
    <img src="{$r->url}/media/{$r->partition}/assets/openid_logo.png" alt="OpenID Logo" />
    </p>

    <p>
    {$r->text.no_mode}<br />
    {$r->text.server}: <b>{$r->text.server_url}</b><br />
    {$r->text.realm}: <b>{$r->text.realm_id}</b><br />
    <a href="{$r->text.login_url}">{$r->text.login}</a>
    {if $r->bool.profile} | <a href="{$r->text.test_url}">{$r->text.test}</a>{/if}

    </p>

</body>
</html>