{capture name=header}

    {if $thread_pos}
         {$r->tinyMceComment()}
    {else}
        {$r->tinyMceEditor()}
    {/if}

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
<legend>{if $id}{$r->gtext.edit_2}{else}{$r->gtext.new}{/if}</legend>

<form action="{$r->text.form_url}" name="{$form_name}" method="post" enctype="multipart/form-data" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $id}
<input type="hidden" name="id" value="{$id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($id)}" />
{validate id="integrity" message="integrity failure"}
{/if}

{if $validate.$form_name.is_error !== false}
<p class="errorWarning">{$r->gtext.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->gtext.form_problem} :</p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="title" message=$r->gtext.error_1}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.title} :</label>
<input type="text" name="title" value="{$title}" class="widerInput" />
{$smarty.capture.error}
</p>

{if !$thread_pos}

    {if $image}
    <!-- Current image -->
    <p><em>{$r->gtext.click_to_crop}</em></p>
    <p>
    <a href="{$r->makeUrl('/cropper/blog')}/{$id}" class="noBg"><img src="{$r->url}/data/blog/{$image|escape:'url'}" alt="" border="0" class="croppable" /></a>
    </p>

    <p>
    <label for="unset_image">{$r->gtext.unset_image} :</label>
    <input type="checkbox" name="unset_image" id="unset_image" value="1" {if $unset_image}checked="checked"{/if} /><br />
    </p>
    {/if}

    <p>
    {strip}
        {capture name=error}
        {validate id="image" message=$r->gtext.error_3}
        {validate id="image2" message=$r->gtext.error_4}
        {/capture}
    {/strip}
    <label for="image" {if $smarty.capture.error}class="error"{/if} >{$r->gtext.image} : </label>
    <input type="file" name="image" id="image" class="imageFile" />
    {$smarty.capture.error}
    </p>

    <p>
    {$r->gtext.max_filesize}: {$r->text.upload_max_filesize}<br />
    {$r->gtext.extensions}: {$r->text.supported}
    </p>

{/if}

<p>
{strip}
    {capture name=error}
    {validate id="body" message=$r->gtext.error_2}
    {/capture}
{/strip}
<span {if $smarty.capture.error}class="error"{/if}>{$r->gtext.body} : </span> {$smarty.capture.error}
</p>

<p>
<textarea name="body" class="mceEditor" rows="10" cols="80" >{$body}</textarea>
</p>

{if !$thread_pos}
    <p>
    <label for="draft">{$r->gtext.save_draft} :</label>
    <input type="checkbox" name="draft" id="draft" value="1" {if $draft}checked="checked"{/if} />
    </p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="date" message=$r->gtext.error_5}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.date} :</label>
<span class="htmlSelect">
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD'  start_year='-5' end_year='+1'}
</span>
{$smarty.capture.error}
</p>

<p>
{strip}
    {capture name=error}
    {validate id="time" message=$r->gtext.error_6}
    {validate id="time2" message=$r->gtext.error_6}
    {validate id="time3" message=$r->gtext.error_6}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.time} :</label>
<span class="htmlSelect">
{html_select_time time="$Time_Hour:$Time_Minute:$Time_Second" use_24_hours=true}
</span>

{$smarty.capture.error}
</p>

{if !$thread_pos}

    <!-- Regular tags -->
    <p>
    <label>{$r->gtext.tags_2} :</label>
    <input type="text" name="tags" value="{$tags}" class="widerInput" />
    </p>

    <!-- Bayesian categories -->
    {capture name=tags}
        {foreach from=$r->getTrainerVectors() key=k item=v}
        {$v}: <span class="htmlSelect">{html_options name='category_id[]' options=$r->getCategoriesByVector($k) selected=$category_id}</span>
        {/foreach}
    {/capture}

    {if $smarty.capture.tags|trim}
    <p>{$smarty.capture.tags}</p>
    {if $linked}<p><em>{$r->gtext.linked_to}: {$linked}</em></p>{/if}
    {/if}

{/if}


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