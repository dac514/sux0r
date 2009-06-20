{capture name=header}
    <meta name='robots' content='noindex,nofollow' />
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

{$r->gtext.accept_mode}
<br />
<b>{$r->text.unaccepted_url}</b>
<br /><br />
{$r->gtext.continue}
<br />
<a href="{$r->text.always_url}">{$r->gtext.always}</a> | <a href="{$r->text.yes_url}">{$r->gtext.yes}</a> | <a href="{$r->text.no_url}">{$r->gtext.no}</a>

</div></div>

{include file=$r->xhtml_footer}