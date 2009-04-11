<div class="userinfo">

    {if $r->bool.root}<span id='adminLink'>[ <a href='{$r->makeUrl('/admin')}'>{$r->gtext.admin}</a> ]</span>{/if}
    <strong>{$r->gtext.welcome}:</strong> <a href='{$r->makeUrl('/user/profile')}/{$r->text.nickname}'>{$r->text.nickname}</a> |
    <a href='{$r->makeUrl('/user/logout')}'>{$r->gtext.logout}</a>

</div>
