{include file=$r->xhtml_header}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
</div>
<div class="clearboth"></div>

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->text.openid_reg}</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{/if}

<p>
{strip}
    {capture name=error1}
    {validate id="url" message=$r->text.form_error_8}
    {validate id="url2" message=$r->text.form_error_9}
    {validate id="url3" message=$r->text.form_error_10}
    {/capture}
{/strip}

<span {if $smarty.capture.error1}class="error"{/if}>{$r->text.url} :</span>
<input type="text" name="url" value="{if $url}{$url}{else}http://{/if}" class="openidInput" />
{$smarty.capture.error1}
</p>

<p>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" value="{$r->text.submit}" class="button" />
</p>

</form>

</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}