{capture name=header}

{$r->cropperInit($x2, $y2)}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}


    <p>
    <form>

    <input type="hidden" name="x2" value="{$x2}" />
    <input type="hidden" name="y2" value="{$y2}" />
    <label for="x1">x1:</label><input type="text" name="x1" id="x1" size="4" />
    <label for="y1">y1:</label><input type="text" name="y1" id="y1" size="4" />
    <label for="width">width:</label><input type="text" name="width" id="width" size="4" />
    <label for="height">height:</label><input type="text" name="height" id="height" size="4" />

    </form>
    </p>

    <p>
    <img id="cropperImage" src="{$url_to_source}" alt="Cropper" width="{$width_source}" height="{$height_source}" />
    </p>

    <div id="previewArea" style="margin: 20px; 0 0 20px;">xxx</div>


{include file=$r->xhtml_footer}