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

{validate id="url" message="One or more URL is empty" append="error"}
{validate id="url2" message="One or more URL is invalid" append="error"}
{validate id="url3" message="One or more URL already exists" append="error"}
{validate id="title" message="One or more title is empty" append="error"}
{validate id="body" message="One or more body is empty" append="error"}


{if $error}
    <p class="errorWarning">{$r->text.form_error} :</p>
    <ul class="error" style="padding-bottom: 10px;">
    {foreach from=$error item=v}
        <li>{$v}</li>
    {/foreach}</p>
    </ul>
{/if}

{foreach from=$r->found_links key=k item=v name=foo}

    <p>
    <label for="url[]">URL :</label>
    <input type="text" name="url[]" value="{$k}" />
    {$smarty.capture.error}
    </p>

    <p>
    <label for="title[]">Title :</label>
    <input type="text" name="title[]" value="{$v.title}" />
    </p>

    <p>Body :</p>
    <p><textarea name="body[]" class="mceEditor">{$v.body}</textarea></p>

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