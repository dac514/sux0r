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
<legend>{$r->text.upload_2}</legend>

<form action="{$r->text.form_url}" name="default" method="post" enctype="multipart/form-data" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

<p>
{strip}
    {capture name=error2}
    {validate id="album" message=$r->text.form_error_3}
    {/capture}
{/strip}

<label {if $smarty.capture.error2}class="error"{/if}>{$r->text.album} :</label>
{html_options name='album' options=$r->getAlbums() selected=$album}
{$smarty.capture.error2}
</p>


<p>
{strip}
    {capture name=error1}
    {validate id="image" message=$r->text.form_error_1}
    {validate id="image2" message=$r->text.form_error_2}
    {/capture}
{/strip}

<label {if $smarty.capture.error1}class="error"{/if}>{$r->text.upload} :</label>
<input type="file" name="image" class="imageFile" />
{$smarty.capture.error1}
</p>

<p>
{$r->text.max_filesize}: {$r->text.upload_max_filesize}<br />
{$r->text.extensions}: {$r->text.supported}
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