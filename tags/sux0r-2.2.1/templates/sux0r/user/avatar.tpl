{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
    <div class='clearboth'></div>
</div>

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->gtext.avatar}: {$nickname}</legend>

<form action="{$r->text.form_url}" name="{$form_name}" method="post" enctype="multipart/form-data" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

<input type="hidden" name="nickname" value="{$nickname}" />
<input type="hidden" name="users_id" value="{$users_id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($users_id, $nickname)}" />
{validate id="integrity" message="integrity failure"}

{if $validate.$form_name.is_error !== false}
<p class="errorWarning">{$r->gtext.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->gtext.form_problem} :</p>
{/if}


{if $image}
<!-- Current image -->
<p><em>{$r->gtext.click_to_crop}</em></p>
<p>
<a href="{$r->makeUrl('/cropper/user')}/{$users_id}" class="noBg"><img src="{$r->url}/data/user/{$image|escape:'url'}" alt="" border="0" class="croppable" /></a>
</p>

<p>
<label for="unset_image" >{$r->gtext.unset_image} :</label>
<input type="checkbox" name="unset_image" id="unset_image" value="1" {if $unset_image}checked="checked"{/if} /><br />
</p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="image" message=$r->gtext.form_error_14}
    {validate id="image2" message=$r->text.form_error_15}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.image} : </label>
<input type="file" name="image" class="imageFile" />
{$smarty.capture.error}
</p>

<p>
{$r->gtext.max_filesize}: {$r->text.upload_max_filesize}<br />
{$r->gtext.extensions}: {$r->text.supported}
</p>


<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->gtext.submit}" />
</p>

</form>
</fieldset>


</div>

</div>

{include file=$r->xhtml_footer}