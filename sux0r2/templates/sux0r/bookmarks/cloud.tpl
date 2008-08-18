{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    <h1>sux0r - it sux0rs up all the web</h1>
    {insert name="userInfo"}
    {$r->navlist()}
</div>
<div class="clearboth"></div>

<div id="middle" style="text-align: center; margin: 20px;" >
    {* Tagcloud *}
    {$r->tagcloud($r->tc)}
</div>

<div id="footer">
    Footer
</div>


</div>

{include file=$r->xhtml_footer}