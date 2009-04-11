<div class="userinfo">

    <a href='{$r->makeUrl('/user/login')}'>{$r->gtext.login}</a>
    <a class='noBg' href='{$r->makeUrl('/user/login/openid')}'><img src='{$r->url}/media/{$r->partition}/assets/openid_icon.gif' alt='OpenID Login' class='openidLogin' /></a> |
    <a href='{$r->makeUrl('/user/register')}'>{$r->gtext.register}</a>
    <a class='noBg' href='{$r->makeUrl('/user/register/openid')}'><img src='{$r->url}/media/{$r->partition}/assets/openid_icon.gif' alt='OpenID Login' class='openidLogin' /></a>

</div>
