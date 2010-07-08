<div id='navcontainer'>
<ul id='navlist'>
{foreach from=$r->arr.list key=foo item=bar name=nav}
	<li>
	{if $bar|is_array && $bar.1|is_array}
        <a href='{$bar.0}'
        {if $foo == $r->text.selected}class='selected'{/if}
        onmouseover="mopen('navm{$smarty.foreach.nav.iteration}')"
        onmouseout="mclosetime()">{$foo}</a>
        <div id="navm{$smarty.foreach.nav.iteration}"
            onmouseover="mcancelclosetime()"
            onmouseout="mclosetime()">
		{foreach from=$bar.1 key=foo2 item=bar2}
		<a href="{$bar2}">{$foo2}</a>
		{/foreach}
        </div>
	{elseif $bar|is_array}
        <a href='{$bar.0}' {if $foo == $r->text.selected}class='selected'{/if}>{$foo}</a>
	{else}
        <a href='{$bar}' {if $foo == $r->text.selected}class='selected'{/if}>{$foo}</a>
	{/if}
	</li>
{/foreach}
</ul>
</div>
<div class='clearboth'></div>

