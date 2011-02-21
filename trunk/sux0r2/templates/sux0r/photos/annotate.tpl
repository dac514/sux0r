{capture name=header}

    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>
    <script src="{$r->url}/includes/symbionts/scriptaculous/src/scriptaculous.js" type="text/javascript"></script>

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
<legend>{$r->gtext.annotate_2}</legend>

<form action="{$r->text.form_url}" name="{$form_name}" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $id}
<input type="hidden" name="id" value="{$id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($id)}" />
{validate id="integrity" message="integrity failure"}
{/if}

{if $r->arr.photos}

<p><em>{$r->gtext.annotate_tip}</em></p>

{foreach from=$r->arr.photos item=foo}

    <div class="annotateItem" style="">

    <div style="float:left;">
    <a href="{$r->makeUrl('/cropper/photos')}/{$foo.id}" class="noBg"><img src="{$r->url}/data/photos/{$foo.image|escape:'url'}?sid={$r->uniqueId()}" alt="" width="{#thumbnailWidth#}" height="{#thumbnailHeight#}" border="0" class="croppable" /></a>
    </div>

    <div class="annotateItemDesc" style="float:left;">
        <div id="editme{$foo.id}">{if $foo.description}{$foo.description}{else}{$r->gtext.clickme}{/if}</div>
    </div>

    <div class="annotateItemOptions" style="float:right;">
        <input type="radio" name="cover" id="cover{$foo.id}" value="{$foo.id}" {if $cover == $foo.id}checked="checked"{/if} /> <label for="cover{$foo.id}">{$r->gtext.cover}</label> |
        <input type="checkbox" name="delete[]" id="delete{$foo.id}" value="{$foo.id}" /> <label for="delete{$foo.id}">{$r->gtext.delete}</label>
    </div>


    {literal}
    <script type="text/javascript">
    // <![CDATA[
    new Ajax.InPlaceEditor(
        'editme{/literal}{$foo.id}{literal}',
        '{/literal}{$r->url}{literal}/modules/photos/ajax.describe.php', {
            rows: 5,
            cols: 80,
            clickToEditText: '{/literal}{$r->gtext.clickme}{literal}',
            savingText: '{/literal}{$r->gtext.saving}{literal}...',
            okControl: 'button',
            okText: '{/literal}{$r->gtext.ok}{literal}',
            cancelControl: 'button',
            cancelText: '{/literal}{$r->gtext.cancel}{literal}',
            callback: function(form, value) {
                return 'id={/literal}{$foo.id}{literal}&description='+encodeURIComponent(value)
            }
        });
    // ]]>
    </script>
    {/literal}

    <div class="clearboth"></div>

    </div>

{/foreach}


{$r->text.pager}
{else}
    <p>{$r->gtext.no_photos}...</p>
{/if}

<p><br />
<input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->gtext.submit}" />
</p>


</form>
</fieldset>

</div>

</div>

{include file=$r->xhtml_footer}