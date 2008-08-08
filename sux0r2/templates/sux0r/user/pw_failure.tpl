{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

<h1>{$r->text.pw_failure}</h1>
<p>{$r->text.pw_failure_2}</p>

</div></div>

{include file=$r->xhtml_footer}