<div id='navcontainer'>
<ul id='navlist'>
{foreach from=$r->arr.list key=foo item=bar}
    <li><a href='{$bar}' {if $foo == $r->text.selected}class='selected'{/if}>{$foo}</a></li>
{/foreach}
</ul>
</div>
<div class='clearboth'></div>

