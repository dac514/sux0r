{capture name=header}

    <style type="text/css">
    label {ldelim}
        float: left;
        width: 100px;
        margin-right: 0.5em;
        text-align: right;
        height: 1em;
    {rdelim}
    </style>

    {literal}
    <script type='text/javascript'>
    // <![CDATA[
    function deleteWarning() {
        if (document.forms[0].delete_user.checked) {
            if (!confirm('{/literal}{$r->gtext.alert_delete|escape:'javascript'}{literal}')) {
                document.forms[0].delete_user.checked = false;
            }
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
<legend>{$r->gtext.access}: {$nickname}</legend>

<form action="{$r->text.form_url}" name="{$form_name}" method="post" accept-charset="utf-8" >
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

{if $disabled}<p><label>&nbsp;</label>{$r->gtext.yourself}</p>{/if}

<p>
<label for="root">{$r->gtext.root} :</label>
<input type="checkbox" name="root" id="root" value="1" {if $root}checked="checked"{/if} {$disabled} /><br />
</p>

{foreach from=$myOptions key=k item=v}
   <p><label>{$k|mb_ucwords} :</label> {html_options name=$k options=$v selected=$mySelect.$k}</p>
{/foreach}

<p>
<label for="banned">{$r->gtext.banned} :</label>
<input type="checkbox" name="banned" id="banned" value="1" {if $banned}checked="checked"{/if} {$disabled} /><br />
</p>

<p>
<label for="delete_user">{$r->gtext.delete} :</label>
<input type="checkbox" name="delete_user" id="delete_user" value="1" {$disabled} onclick='deleteWarning()'  /><br />
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