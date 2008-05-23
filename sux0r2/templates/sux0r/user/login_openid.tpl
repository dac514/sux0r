<div id="proselytizer">

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

<fieldset>
<legend>{$r->text.openid_login}</legend>

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{/if}

<p>
{strip}
    {capture name=error1}
    {validate id="url" message=$r->text.form_error_8}
    {validate id="url2" message=$r->text.form_error_9}
    {/capture}
{/strip}

<span {if $smarty.capture.error1}class="error"{/if}>{$r->text.url} :</span>
<input type="text" name="url" value="{if $url}{$url}{else}http://{/if}" class="openidInput" />
{$smarty.capture.error1}
</p>

<p>
<input type="submit" value="{$r->text.submit}" class="button" />
</p>

</fieldset>

</form>

</div>