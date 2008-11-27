{capture name=header}

    <style type="text/css">
    label {ldelim}
        float: left;
        width: 100px;
        margin-right: 0.5em;
        padding-top: 0.2em;
        text-align: right;
    {rdelim}
    </style>

    {literal}
    <script type='text/javascript'>
    // <![CDATA[
    function rm(myForm, myWarning) {
        if(confirm(myWarning)) {
            var x = document.getElementsByName(myForm);
            x[0].submit();
        }
    }
    // ]]>
    </script>
    {/literal}

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
<legend>{$r->text.admin_purge}</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

<p>Delete everything up until: </p>

<p>
{strip}
    {capture name=error}
    {validate id="date" message=$r->text.error_1}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->text.date} :</label>
<span class="htmlSelect">
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD' start_year='-5' reverse_years=true}
</span>
{$smarty.capture.error}
</p>

<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="button" class="button" value="{$r->text.submit}" onclick="rm('default', '{$r->text.alert_purge}');" />
</p>

</form>
</fieldset>


</div>

</div>

{include file=$r->xhtml_footer}