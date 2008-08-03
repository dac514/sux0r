{capture name=header}

    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>
    <script src="{$r->url}/includes/symbionts/scriptaculous/src/scriptaculous.js" type="text/javascript"></script>

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Content *}
<div id="middle">

<fieldset>
<legend>Annotate</legend>

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $id}
<input type="hidden" name="id" value="{$id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($id)}" />
{validate id="integrity" message="integrity failure"}
{/if}

{if $r->pho}
{foreach from=$r->pho item=foo name=bar}

    <div style="border: 1px dashed #ccc; padding: 10px; margin: 10px;">

    <div style="float:left;">
    <a href="{$r->makeUrl('/cropper/photos')}/{$foo.id}"><img src="{$r->url}/data/photos/{$foo.image}?time={php}echo time();{/php}" alt="" width="{#thumbnailWidth#}" height="{#thumbnailHeight#}" border="0" /></a>
    </div>

    <div style="float:left; margin-left: 10px; width: 600px;">
        <div id="editme{$foo.id}">{if $foo.description}{$foo.description}{else}Click me to edit this nice long text.{/if}</div>
    </div>

    <div style="float:right;text-align: right; width: 180px">
        <input type="radio" name="cover" id="cover{$foo.id}" value="{$foo.id}" {if $cover == $foo.id}checked="checked"{/if} /><label for="cover{$foo.id}">Album Cover</label> |
        <input type="checkbox" name="delete[]" id="delete{$foo.id}" value="{$foo.id}" /><label for="delete{$foo.id}">Delete</label>
    </div>


    {literal}
    <script type="text/javascript">
    // <![CDATA[
    new Ajax.InPlaceEditor(
        'editme{/literal}{$foo.id}{literal}',
        '/sux0r2/modules/photos/describe.php', {
            rows: 5,
            cols: 80,
            clickToEditText: 'Click to edit',
            savingText: 'Saving',
            okControl: 'button',
            okText: 'Ok',
            cancelControl: 'button',
            cancelText: 'Cancel',
            callback: function(form, value) {
                return 'id={/literal}{$foo.id}{literal}&description='+escape(value)
            }
        });
    // ]]>
    </script>
    {/literal}

    <div class="clearboth"></div>


    </div>

{/foreach}

<div style="margin: 10px;">
    <p>{$r->text.pager}</p>
    <p>
    <input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
    <input type="submit" class="button" value="{$r->text.submit}" />
    </p>
</div>

{/if}



</form>
</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}