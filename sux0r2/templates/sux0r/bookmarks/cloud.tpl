{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->gtext.bookmarks}" href="{$r->makeUrl('/bookmarks/rss', null, true)}" />

    {literal}
    <style type="text/css">
    #proselytizer { border-color: #ffffff; }
    </style>
    {/literal}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    <h1 onclick="document.location='{$r->makeUrl('/home')}'" style="cursor:pointer">{$r->gtext.header|lower}</h1>
    {insert name="userInfo"}
    {$r->navlist()}
</div>
<div class="clearboth"></div>

<div id="middle" class="tagcloud" >
    {* Tagcloud *}
    {$r->tagcloud($r->arr.tc)}
</div>

<div id="footer">
    {$r->copyright()}
</div>


</div>

{include file=$r->xhtml_footer}