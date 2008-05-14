<p>
<img src="{$r->url}/media/{$r->partition}/assets/openid_logo.png" alt="OpenID Logo" />
</p>

<p>
{$r->text.no_mode}<br />
{$r->text.server}: <b>{$r->text.server_url}</b><br />
{$r->text.realm}: <b>{$r->text.realm_id}</b><br />
<a href="{$r->text.login_url}">{$r->text.login}</a>
{if $r->bool.profile} | <a href="{$r->text.test_url}">{$r->text.test}</a>{/if}
</p>
