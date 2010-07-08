{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">
    <div id="middle">

        <p>Welcome to the ICBL REST API, programmed by Santy Chumbe</p>

    </div>
</div>

{include file=$r->xhtml_footer}
