<div class="widget">

<h2>{if $r->text.url_title}<a href="{$r->text.url_title}">{$r->text.title}</a>{else}{$r->text.title}{/if}</h2>

<div class="widgetContent">
{if $r->text.image}{strip}
    <div class="{$r->text.floater}">
    {if $r->text.url_image}<a href="{$r->text.url_image}">{$r->text.image}</a>{else}{$r->text.image}{/if}
    {if $r->text.caption && $r->arr.size.0}<div style="width:{$r->arr.size.0}px;">{$r->text.caption}</div>{/if}
    </div>
{/strip}{/if}
{$r->text.content}
</div>

<div class="clearboth"></div>
<b class="bb"><b></b></b>
</div>
