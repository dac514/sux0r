{capture name=header}
    <meta name='robots' content='noindex,nofollow' />
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

<p>{$r->text.message}</p>

</div></div>

{include file=$r->xhtml_footer}