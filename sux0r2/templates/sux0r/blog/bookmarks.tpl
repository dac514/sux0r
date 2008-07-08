{capture name=header}

{$r->tinyMceBookmark()}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>Bookmarks</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

{foreach from=$r->found_links key=k item=v name=foo}

    {assign var='error' value=null}

    {capture assign=var}url[{$smarty.foreach.foo.index}]{/capture}
    {validate id=$var message="URL is empty" append="error"}

    {capture assign=var}url2[{$smarty.foreach.foo.index}]{/capture}
    {validate id=$var message="URL is invalid" append="error"}

    <p>
    <label {if $error}class="error"{/if} for="url[{$smarty.foreach.foo.index}]">URL :</label>
    <input type="text" name="url[{$smarty.foreach.foo.index}]" value="{if !is_numeric($k)}{$k}{/if}" />
    {if $error}{foreach from=$error item=v2}{$v2}{/foreach}{/if}
    </p>

    {assign var='error' value=null}

    {capture assign=var}title[{$smarty.foreach.foo.index}]{/capture}
    {validate id=$var message="Title is empty" append="error"}

    <p>
    <label {if $error}class="error"{/if} for="title[{$smarty.foreach.foo.index}]">Title :</label>
    <input type="text" name="title[{$smarty.foreach.foo.index}]" value="{$v.title}" />
    {if $error}{foreach from=$error item=v2}{$v2}{/foreach}{/if}
    </p>

    {assign var='error' value=null}

    {capture assign=var}body[{$smarty.foreach.foo.index}]{/capture}
    {validate id=$var message="Body is empty" append="error"}

    <p>
    <span {if $error}class="error"{/if} >Body :</span> {if $error}{foreach from=$error item=v2}{$v2}{/foreach}{/if}
    </p>

    <p><textarea name="body[{$smarty.foreach.foo.index}]" class="mceEditor">{$v.body}</textarea></p>

    <div style="padding-bottom: 10px;"></div>

{/foreach}

<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->text.submit}" />
</p>

</form>


</div>

</div>

{include file=$r->xhtml_footer}