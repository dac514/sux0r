{capture name=header}

{$r->tinyMceInit()}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>Edit</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

<p>
<label>{$r->text.dob} Title :</label>
<input type="text" name="title" value="{$title}" />
</p>

<p>
<textarea name="body_html" class="mceEditor">{$body_html}</textarea>
</p>

<p>
<label>{$r->text.dob} Date :</label>
<span class="htmlSelectDate">
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD' start_year='-3' reverse_years=true}
</span>
</p>

<p>
<label>{$r->text.dob} Time :</label>
<span class="htmlSelectTime">
{html_select_time time="$Time_Hour:$Time_Minute:$Time_Second" use_24_hours=true}
</span>
</p>

<p>
TODO: Tags
</p>

<p>
TODO: Save draft?
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