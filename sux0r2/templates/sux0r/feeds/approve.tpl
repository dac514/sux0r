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
<legend>{$r->text.manage}</legend>
<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

    {if $validate.default.is_error !== false}
    <p class="errorWarning">{$r->text.form_error} :</p>
    {elseif $r->detectPOST()}
    <p class="errorWarning">{$r->text.form_problem} :</p>
    {/if}
       
    {validate id="feeds" message=$r->text.form_problem}    
    {if $r->fp}                
        {foreach from=$r->fp item=foo}                
            <div style="border: 1px dashed #ccc; padding: 10px; margin: 10px;">
                
            <div>
                <a href="{$foo.url}" target="_blank">{$foo.title}</a>
                {if $foo.body_html|strip}<br /><br />{$foo.body_html}{/if}
            </div>
        
            <div style="margin-top: 20px;">
                <a href="{$r->makeUrl('/feeds/edit')}/{$foo.id}">Edit</a> | <a href="{$foo.url}" target="_blank">URL</a> |
                <input type="radio" name="feeds[{$foo.id}]" id="f_k_{$foo.id}" value="1" /><label for="f_k_{$foo.id}" >Approve</label> | 
                <input type="radio" name="feeds[{$foo.id}]" id="f_d_{$foo.id}" value="0" /><label for="f_d_{$foo.id}" >Delete</label>
                                
            </div>   
            
            <div class="clearboth"></div>
    
            </div>            
        
        
        {/foreach}        
    {else}
    Nothing to approve
    {/if}
    

    <p>
    <input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
    <input type="submit" value="{$r->text.submit}" class="button" />
    </p>

</form>
</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}