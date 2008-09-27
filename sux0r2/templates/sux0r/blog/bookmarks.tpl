{capture name=header}

    {$r->tinyMceBookmark()}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->text.suggest_bookmarks}</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

{foreach from=$r->found_links key=k item=v name=foo}

    {assign var='error' value=null}
    {capture assign=var}url[{$smarty.foreach.foo.index}]{/capture} {* variable validate ids *}
    {validate id=$var message=$r->text.error_7 append="error"}
    {capture assign=var}url2[{$smarty.foreach.foo.index}]{/capture}
    {validate id=$var message=$r->text.error_8 append="error"}

    <p>
    <label {if $error}class="error"{/if} for="url[{$smarty.foreach.foo.index}]">{$r->text.url} :</label>
    <input type="text" name="url[{$smarty.foreach.foo.index}]" value="{if !is_numeric($k)}{$k}{/if}" class="widerInput" />
    {if $error}{foreach from=$error item=v2}{$v2}{/foreach}{/if}
    </p>

    {assign var='error' value=null}
    {capture assign=var}title[{$smarty.foreach.foo.index}]{/capture} {* variable validate id *}
    {validate id=$var message=$r->text.error_1 append="error"}

    <p>
    <label {if $error}class="error"{/if} for="title[{$smarty.foreach.foo.index}]">{$r->text.title} :</label>
    <input type="text" name="title[{$smarty.foreach.foo.index}]" value="{$v.title}" class="widerInput" />
    {if $error}{foreach from=$error item=v2}{$v2}{/foreach}{/if}
    </p>

    {assign var='error' value=null}
    {capture assign=var}body[{$smarty.foreach.foo.index}]{/capture} {* variable validate id *}
    {validate id=$var message=$r->text.error_2 append="error"}

    <p>
    <span {if $error}class="error"{/if} >{$r->text.body} :</span> {if $error}{foreach from=$error item=v2}{$v2}{/foreach}{/if}
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