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

{if $r->pho}
{foreach from=$r->pho item=foo name=bar}

<div style="border: 1px dashed #ccc; padding: 10px; margin: 10px;">

<div style="float:left;">
<a href="{$r->makeUrl('/cropper/photos')}/{$foo.id}"><img src="{$r->url}/data/photos/{$foo.image}?time={php}echo time();{/php}" alt="" width="{#thumbnailWidth#}" height="{#thumbnailHeight#}" border="0" /></a>
</div>

<div style="float:left; margin-left: 10px; ">
    <div id="editme{$foo.id}">{if $foo.description}{$foo.description}{else}Click me to edit this nice long text.{/if}</div>
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
{/if}

<div style="margin: 10px;">
    <p>{$r->text.pager}</p>
    <p><input type="button" class="button" value="Back to album" onclick="document.location='{$r->text.back_url}';" /></p>
</div>

</div>

</div>

{include file=$r->xhtml_footer}