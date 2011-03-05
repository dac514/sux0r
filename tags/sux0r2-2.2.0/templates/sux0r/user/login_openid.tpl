{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
</div>
<div class="clearboth"></div>

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->gtext.openid_login}</legend>

<form action="{$r->text.form_url}" name="{$form_name}" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

{if $validate.$form_name.is_error !== false}
<p class="errorWarning">{$r->gtext.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->gtext.form_problem} :</p>
{/if}

<p>
{strip}
    {capture name=error1}
    {validate id="url" message=$r->gtext.form_error_8}
    {validate id="url2" message=$r->gtext.form_error_9}
    {/capture}
{/strip}

<span {if $smarty.capture.error1}class="error"{/if}>{$r->gtext.url} :</span>
<input type="text" name="url" value="{if $url}{$url}{else}http://{/if}" class="openidInput" />
{$smarty.capture.error1}
</p>

<p>
<input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" value="{$r->gtext.submit}" class="button" />
</p>

</form>

</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}