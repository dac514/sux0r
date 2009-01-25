{capture name=header}

{$r->cropperInit($x2, $y2)}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

<div id="middle">

    {if $validate.default.is_error !== false}
    <p class="errorWarning">{$r->gtext.form_error} :</p>
    {elseif $r->detectPOST()}
    <p class="errorWarning">{$r->gtext.form_problem} :</p>
    {/if}

    {validate id="integrity" message="integrity failure"}

    <form action="{$form_url}" name="default" method="post" accept-charset="utf-8" >

    <input type="hidden" name="token" value="{$token}" />
    <input type="hidden" name="integrity" value="{$r->integrityHash($module, $id)}" />

    <input type="hidden" name="module" value="{$module}" />
    <input type="hidden" name="id" value="{$id}" />
    <input type="hidden" name="x2" value="{$x2}" />
    <input type="hidden" name="y2" value="{$y2}" />

    <label for="x1">x1:</label><input type="text" name="x1" id="x1" size="4" />
    <label for="y1">y1:</label><input type="text" name="y1" id="y1" size="4" />
    <label for="width">{$r->gtext.width}:</label><input type="text" name="width" id="width" size="4" />
    <label for="height">{$r->gtext.height}:</label><input type="text" name="height" id="height" size="4" />

    <input class="button" onclick="alert('{$r->gtext.cancelled}'); document.location='{$prev_url}';" value="{$r->gtext.cancel}" type="button" />
    <input class="button" type="submit" value="{$r->gtext.submit}" />

    </form>

    <p>
    <img id="cropperImage" src="{$url_to_source}" alt="Cropper" width="{$width}" height="{$height}" />
    </p>


</div>

</div>

{include file=$r->xhtml_footer}