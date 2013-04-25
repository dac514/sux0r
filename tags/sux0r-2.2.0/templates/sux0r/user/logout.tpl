{capture name=header}

    <script type='text/javascript'>
    // <![CDATA[
    // IE clear HTTP Authentication
    var agt = navigator.userAgent.toLowerCase();
    if (agt.indexOf("msie") != -1) document.execCommand("ClearAuthenticationCache");
    else {
        // TODO: More HTTP Authentication logout hacks?
    }
    // ]]>
    </script>

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer"><div id="middle">

<h1>{$r->gtext.logout}</h1>
<p>{$r->gtext.logout2}</p>
<p style="text-align:center;">[ <a href="{$r->makeUrl('/home')}">{$r->gtext.homepage}</a> ]</p>

</div></div>

{include file=$r->xhtml_footer}
