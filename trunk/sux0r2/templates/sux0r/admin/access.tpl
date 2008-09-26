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

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->text.access}: {$nickname}</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
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

{if $disabled}<p><label>&nbsp;</label>{$r->text.yourself}</p>{/if}

<p>
<label for="root">{$r->text.root} :</label>
<input type="checkbox" name="root" value="1" {if $root}checked="checked"{/if} {$disabled} /><br />
</p>

{foreach from=$myOptions key=k item=v}
   <p><label for="$k">{$k|capitalize} :</label> {html_options name=$k options=$v selected=$mySelect.$k}</p>
{/foreach}

<p>
<label for="banned">{$r->text.banned} :</label>
<input type="checkbox" name="banned" value="1" {if $banned}checked="checked"{/if} {$disabled} /><br />
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