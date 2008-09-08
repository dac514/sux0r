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
<legend>{$r->text.manage}</legend>
<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

    {if $validate.default.is_error !== false}
    <p class="errorWarning">{$r->text.form_error} :</p>
    {elseif $r->detectPOST()}
    <p class="errorWarning">{$r->text.form_problem} :</p>
    {/if}

    {validate id="subscriptions" message=$r->text.form_problem}
    {html_checkboxes name='subscriptions' options=$feeds selected=$subscriptions assign=tmp}
    {html_table loop=$tmp inner=rows table_attr='border="0" class="feedTable"'}

    <p>
    <input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
    <input type="submit" value="{$r->text.submit}" class="button" />
    </p>

</form>
</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}