<div class="widget">
<h2>{$r->text.title}</h2>
<div class="widgetContent">
{if $r->text.image}<div class="{$r->text.floater}">
{$r->text.image}
{if $r->text.caption && $r->text.width}<div style="width:{$width}px;">{$r->text.caption}</div>{/if}
</div>{/if}
{$r->text.content}
</div>
<div class="clearboth"></div>
<b class="bb"><b></b></b>
</div>
