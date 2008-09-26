{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->text.avatar}: {$nickname}</legend>

<form action="{$r->text.form_url}" name="default" method="post" enctype="multipart/form-data" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

<input type="hidden" name="nickname" value="{$nickname}" />
<input type="hidden" name="users_id" value="{$users_id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($users_id, $nickname)}" />
{validate id="integrity" message="integrity failure"}

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}


{if $image}
<!-- Current image -->
<p>
<a href="{$r->makeUrl('/cropper/user')}/{$users_id}"><img src="{$r->url}/data/user/{$image}" alt="" border="0" /></a>
</p>

<p>
<label for="unset_image">{$r->text.unset_image} :</label>
<input type="checkbox" name="unset_image" value="1" {if $unset_image}checked="checked"{/if} /><br />
</p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="image" message=$r->text.form_error_14}
    {validate id="image2" message=$r->text.form_error_15}
    {/capture}
{/strip}
<label for="image" {if $smarty.capture.error}class="error"{/if} >{$r->text.image} : </label>
<input type="file" name="image" class="imageFile" />
{$smarty.capture.error}
</p>

<p>
{$r->text.max_filesize}: {$r->text.upload_max_filesize}<br />
{$r->text.extensions}: {$r->text.supported}
</p>


<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->text.submit}" />
</p>

</form>


</div>

</div>

{include file=$r->xhtml_footer}