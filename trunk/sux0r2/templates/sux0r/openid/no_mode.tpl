{include file=$r->xhtml_header}

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
{$r->text.no_mode}<br />
{$r->text.server}: <b>{$r->text.server_url}</b><br />
{$r->text.realm}: <b>{$r->text.realm_id}</b><br />
</p>

<p>
<a href="{$r->makeUrl('/user/login/openid')}">{$r->text.login}</a>
{if $r->bool.debug} | <a href="{$r->text.test_url}">{$r->text.test}</a>{/if}
 | <a href="{$r->makeUrl('/home')}">{$r->text.homepage}</a>
</p>

</fieldset>

</div></div>

{include file=$r->xhtml_footer}