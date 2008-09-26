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

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->text.edit}</legend>

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
    {validate id="title" message=$r->text.error_1}
    {/capture}
{/strip}
<label for="title" {if $smarty.capture.error}class="error"{/if} >{$r->text.title} :</label>
<input type="text" name="title" value="{$title}" class="widerInput" />
{$smarty.capture.error}
</p>

{if !$thread_pos}

    {if $image}
    <!-- Current image -->
    <p>
    <a href="{$r->makeUrl('/cropper/blog')}/{$id}"><img src="{$r->url}/data/blog/{$image}" alt="" border="0" /></a>
    </p>

    <p>
    <label for="unset_image">{$r->text.unset_image} :</label>
    <input type="checkbox" name="unset_image" value="1" {if $unset_image}checked="checked"{/if} /><br />
    </p>
    {/if}

    <p>
    {strip}
        {capture name=error}
        {validate id="image" message=$r->text.error_3}
        {validate id="image2" message=$r->text.error_4}
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

{/if}

<p>
{strip}
    {capture name=error}
    {validate id="body" message=$r->text.error_2}
    {/capture}
{/strip}
<span {if $smarty.capture.error}class="error"{/if}>{$r->text.body} : </span> {$smarty.capture.error}
</p>

<p>
<textarea name="body" class="mceEditor">{$body}</textarea>
</p>

{if !$thread_pos}
    <p>
    <label for="draft">{$r->text.save_draft} :</label>
    <input type="checkbox" name="draft" value="1" {if $draft}checked="checked"{/if} />
    </p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="date" message=$r->text.error_5}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->text.date} :</label>
<span class="htmlSelect">
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD'  start_year='-5' end_year='+1' }
</span>
{$smarty.capture.error}
</p>

<p>
{strip}
    {capture name=error}
    {validate id="time" message=$r->text.error_6}
    {validate id="time2" message=$r->text.error_6}
    {validate id="time3" message=$r->text.error_6}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->text.time} :</label>
<span class="htmlSelect">
{html_select_time time="$Time_Hour:$Time_Minute:$Time_Second" use_24_hours=true}
</span>

{$smarty.capture.error}
</p>

{if !$thread_pos}

    <!-- Regular tags -->
    <p>
    <label for="tags" >{$r->text.tags_2} :</label>
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
    {if $linked}<p><em>{$r->text.linked_to}: {$linked}</em></p>{/if}
    {/if}

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