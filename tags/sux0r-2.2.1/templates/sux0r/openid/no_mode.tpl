{capture name=header}
<link rel='openid.server' href='{$r->text.server_url}' />
<meta name='robots' content='noindex,nofollow' />
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
</div>
<div class="clearboth"></div>


{* Content *}
<div id="middle">

<fieldset>

<p>
<img src="{$r->url}/media/{$r->partition}/assets/openid_logo.png" alt="OpenID Logo" />
</p>

<p>
{$r->gtext.no_mode}<br />
{$r->gtext.server}: <b>{$r->text.server_url}</b><br />
{$r->gtext.realm}: <b>{$r->text.realm_id}</b><br />
</p>

<p>
<a href="{$r->makeUrl('/user/login/openid')}">{$r->gtext.login}</a>
{if $r->bool.debug} | <a href="{$r->text.test_url}">{$r->gtext.test}</a>{/if}
 | <a href="{$r->makeUrl('/home')}">{$r->gtext.homepage}</a>
</p>

</fieldset>

</div></div>

{include file=$r->xhtml_footer}