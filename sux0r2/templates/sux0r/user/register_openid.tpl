<div id="proselytizer">

{if $validate.default.is_error !== false}
<p>The form was not submitted, see errors below:</p>
{/if}
<p />

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

{$r->text.url} :
{validate id="url" message="[ URL cannot be empty ]"}
{validate id="url2" message="[ Invalid URL ]"}
{validate id="url3" message="[ Duplicate Url ]"}
<input type="text" name="url" value="{$url}" />
<p />

<input type="submit" value="{$r->text.submit}" />
<p />

</form>

</div>
