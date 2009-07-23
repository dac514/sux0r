{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">
    <div id="middle">


    <h1>{$r->text.status}</h1>
    <p>{$r->text.message}</p>

    <address>{$r->text.signature}</address>


    </div>
</div>

{include file=$r->xhtml_footer}

