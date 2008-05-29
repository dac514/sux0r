{include file=$r->xhtml_header}

<div id="proselytizer"><div id="middle">

<h1>{$r->text.logout}</h1>
<p>{$r->text.logout2}</p>
<p style="text-align:center;">[ <a href="{$r->makeUrl('/home')}">{$r->text.homepage}</a> ]</p>

</div></div>

{include file=$r->xhtml_footer}
