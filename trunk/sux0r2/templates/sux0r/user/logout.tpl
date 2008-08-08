{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

<h1>{$r->text.logout}</h1>
<p>{$r->text.logout2}</p>
<p style="text-align:center;">[ <a href="{$r->makeUrl('/home')}">{$r->text.homepage}</a> ]</p>

</div></div>

{include file=$r->xhtml_footer}
