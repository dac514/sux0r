{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<meta http-equiv="refresh" content="5;url={$r->text.back_url}">
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

    <p><strong>{$r->text.success}</strong>
    <p>{$r->text.success2}</p>
    <p style="text-align:center;">[ <a href="{$r->text.back_url}">{$r->text.back}</a> ]</p>

</div></div>

{include file=$r->xhtml_footer}