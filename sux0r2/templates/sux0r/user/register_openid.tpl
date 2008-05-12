<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type" />
    <title>suxRegister</title>
</head>

<body>


{if $validate.default.is_error !== false}
<p>The form was not submitted, see errors below:</p>
{/if}
<p />


<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

{$r->text.url} :
{validate id="url" message="[ URL cannot be empty ]"}
{validate id="url2" message="[ Invalid URL ]"}
{validate id="url3" message="[ Duplicate Url]"}
<input type="text" name="url" value="{$url}" />
<p />

<input type="submit" value="{$r->text.submit}" />
<p />


</form>


</body>
</html>