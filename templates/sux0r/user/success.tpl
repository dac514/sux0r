{capture name=header}
<meta http-equiv="refresh" content="5;url={$r->text.back_url}">
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

{if $r->bool.edit}

    {* Edit Mode *}

    <p><strong>{$r->gtext.success}</strong>
    <p>{$r->gtext.success_edit}</p>
    <p style="text-align:center;">[ <a href="{$r->text.back_url}">{$r->gtext.back}</a> ]</p>

{else}

    {* Register Mode *}

    <h1>{$r->gtext.thanks}</h1>
    <p>{$r->gtext.success_register}</p>
    <p style="text-align:center;">[ <a href="{$r->makeUrl('/home')}">{$r->gtext.homepage}</a> ]</p>

{/if}

</div></div>

{include file=$r->xhtml_footer}