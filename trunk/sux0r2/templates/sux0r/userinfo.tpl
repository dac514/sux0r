
<div class="userinfo">

    {if $nickname}
        <strong>{$text.welcome}:</strong> <a href="{$url_profile}">{$nickname}</a> |
        <a href="{$url_logout}">{$text.logout}</a>
    {else}
        <a href="{$url_login}">{$text.login}</a>
        <a href="{$url_login_openid}"><img src="{$url}/media/{$partition}/assets/openid_icon.gif" alt="OpenID Login" class="openidLogin" /></a> |
        <a href="{$url_register}">{$text.register}</a>
        <a href="{$url_register_openid}"><img src="{$url}/media/{$partition}/assets/openid_icon.gif" alt="OpenID Login" class="openidLogin" /></a>
    {/if}

</div>
