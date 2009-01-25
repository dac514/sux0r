{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
</div>
<div class="clearboth"></div>

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->gtext.approve}</legend>
<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

    {if $validate.default.is_error !== false}
    <p class="errorWarning">{$r->gtext.form_error} :</p>
    {elseif $r->detectPOST()}
    <p class="errorWarning">{$r->gtext.form_problem} :</p>
    {/if}

    {validate id="bookmarks" message=$r->gtext.form_problem}
    {if $r->arr.bookmarks}
        {foreach from=$r->arr.bookmarks item=foo}
            <div class="approveItem">

            <div>
                <a href="{$foo.url}" target="_blank">{$foo.title}</a>
                {if $foo.body_html|strip}<br /><br />{$foo.body_html}{/if}
            </div>

            <div class="approveItemOptions" >
                <a href="{$r->makeUrl('/bookmarks/edit')}/{$foo.id}">{$r->gtext.edit}</a> | <a href="{$foo.url}" target="_blank">{$r->gtext.url}</a> |
                <input type="radio" name="bookmarks[{$foo.id}]" id="f_k_{$foo.id}" value="1" /><label for="f_k_{$foo.id}" >{$r->gtext.approve_2}</label> |
                <input type="radio" name="bookmarks[{$foo.id}]" id="f_d_{$foo.id}" value="0" /><label for="f_d_{$foo.id}" >{$r->gtext.delete}</label> |
                <em>{$r->gtext.suggested} <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></em>
            </div>

            <div class="clearboth"></div>

            </div>


        {/foreach}
    {else}
        {$r->gtext.nothing}
    {/if}


    <p>
    <input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
    <input type="submit" value="{$r->gtext.submit}" class="button" />
    </p>

</form>
</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}