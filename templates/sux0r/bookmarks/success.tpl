{capture name=header}
<meta http-equiv="refresh" content="5;url={$r->text.back_url}">
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

    <p><strong>{$r->gtext.success}</strong>
    <p>{$r->gtext.success2}</p>
    <p style="text-align:center;">[ <a href="{$r->text.back_url}">{$r->gtext.back}</a> ]</p>

</div></div>

{include file=$r->xhtml_footer}