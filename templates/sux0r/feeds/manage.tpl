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
<legend>{$r->gtext.manage}</legend>
<form action="{$r->text.form_url}" name="{$form_name}" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

    {if $validate.$form_name.is_error !== false}
    <p class="errorWarning">{$r->gtext.form_error} :</p>
    {elseif $r->detectPOST()}
    <p class="errorWarning">{$r->gtext.form_problem} :</p>
    {/if}

    {validate id="subscriptions" message=$r->gtext.form_problem}
    {html_checkboxes name='subscriptions' options=$r->arr.feeds selected=$r->arr.subscriptions assign=tmp}
    {html_table loop=$tmp inner=rows table_attr='border="0" class="feedTable"' cols=3}

    <p><br />
    <input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
    <input type="submit" value="{$r->gtext.submit}" class="button" />
    </p>

</form>
</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}