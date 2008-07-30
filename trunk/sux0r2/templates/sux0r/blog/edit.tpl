{capture name=header}

{$r->tinyMceEditor()}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>Edit</legend>

<form action="{$r->text.form_url}" name="default" method="post" enctype="multipart/form-data" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $id}
<input type="hidden" name="id" value="{$id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($id)}" />
{validate id="integrity" message="integrity failure"}
{/if}

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="title" message="title cannot be empty"}
    {/capture}
{/strip}
<label for="title" {if $smarty.capture.error}class="error"{/if} >{$r->text.dob} Title :</label>
<input type="text" name="title" value="{$title}" class="widerInput" />
{$smarty.capture.error}
</p>

{if $image}
<!-- Current image -->
<p>
<a href="{$r->makeUrl('/cropper/blog')}/{$id}"><img src="{$r->url}/data/blog/{$image}" alt="" border="0" /></a>
</p>

<p>
<label for="unset_image">Unset current image:</label>
<input type="checkbox" name="unset_image" value="1" {if $unset_image}checked="checked"{/if} /><br />
</p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="image" message="invalid file type, jpg, gif, and png only!"}
    {/capture}
{/strip}
<label for="image" {if $smarty.capture.error}class="error"{/if} >Image: </label>
<input type="file" name="image" class="imageFile" />
{$smarty.capture.error}
</p>

<p>
{strip}
    {capture name=error}
    {validate id="body" message="Body cannot be empty"}
    {/capture}
{/strip}
<span {if $smarty.capture.error}class="error"{/if}>Body: </span> {$smarty.capture.error}
</p>

<p>
<textarea name="body" class="mceEditor">{$body}</textarea>
</p>

<p>
<label for="draft">Save as draft:</label>
<input type="checkbox" name="draft" value="1" {if $draft}checked="checked"{/if} />
</p>

<p>
{strip}
    {capture name=error}
    {validate id="date" message="invalid date"}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->text.dob} Date :</label>
<span class="htmlSelect">
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD'  start_year='-5' end_year='+1' }
</span>
{$smarty.capture.error}
</p>

<p>
{strip}
    {capture name=error}
    {validate id="time" message="invalid time"}
    {validate id="time2" message="invalid time"}
    {validate id="time3" message="invalid time"}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->text.dob} Time :</label>
<span class="htmlSelect">
{html_select_time time="$Time_Hour:$Time_Minute:$Time_Second" use_24_hours=true}
</span>

{$smarty.capture.error}
</p>

<!-- Bayesian tags -->

{capture name=tags}
    {foreach from=$r->getTrainerVectors() key=k item=v}
    {$v}: <span class="htmlSelect">{html_options name='category_id[]' options=$r->getCategoriesByVector($k) selected=$category_id}</span>
    {/foreach}
{/capture}

{if $smarty.capture.tags|trim}
<p>{$smarty.capture.tags}</p>
{if $linked}<p><em>{$r->text.linked_to}: {$linked}</em></p>{/if}
{/if}



<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->text.submit}" />
</p>

</form>


</div>

</div>

{include file=$r->xhtml_footer}