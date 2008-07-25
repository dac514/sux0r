{*
   Shared component called from suxRenderer.
   Because of this, it does not use the $r-> convention.
*}

<div class="widget">
<h2>{$title}</h2>
<div class="widgetContent">
{if $image}<div class="{$floater}">
{$image}
{if $caption && $width}<div style="width:{$width}px;">{$caption}</div>{/if}
</div>{/if}
{$content}
</div>
<div class="clearboth"></div>
<b class="bb"><b></b></b>
</div>
