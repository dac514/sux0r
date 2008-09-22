{capture name=header}

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

{* Content *}
<div id="middle">

<fieldset>
<legend>Edit OpenIDs: {$nickname}</legend>

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

<p>The following OpenIDs are currently attached to your account. You can use any of them to sign in:</p>


            <ul style="list-style-type: none;">
            {foreach from=$r->openids item=foo}
            <li><input type="checkbox" name="detach[]" value="{$foo.openid_url}" style="width: auto;" /> {$foo.openid_url}</li>
            {foreachelse}
            <li><span style="margin-left: 2em">Nothing here...</span></li>
            {/foreach}
            <li><span style="margin-left: 2em"><a href="{$r->makeUrl('/user/register/openid')}">Register a new OpenID &raquo;</a></span></li>
            </ul>


<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="button" class="button" value="{$r->text.detach}" onclick="rm('default', '{$r->text.alert_detach}');" />
</p>

</form>


</div>

</div>

{include file=$r->xhtml_footer}