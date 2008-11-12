{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
    <div class='clearboth'></div>
</div>

    <div id="middle">

    <fieldset>
    <legend>{$r->text.admin}</legend>
    
    <ul id="adminMenu">
        <li><a href="{$r->makeURL('/blog/admin')}">{$r->text.admin_blog}</a></li>
        <li><a href="{$r->makeURL('/photos/admin')}">{$r->text.admin_photos}</a></li>
        {insert name="feedsApproveLi"}
        {insert name="bookmarksApproveLi"}    
    </ul>     

    <table class="adminTable">
    <thead>
        <tr>
            <td {if $sort == 'nickname'}class="selected"{/if}><a href="{$nickname_sort_url|escape:'html'}">{$r->text.nickname|lower}</a></td>
            <td {if $sort == 'banned'}class="selected"{/if}><a href="{$banned_sort_url|escape:'html'}">{$r->text.banned|lower}</a></td>
            <td {if $sort == 'root'}class="selected"{/if}><a href="{$root_sort_url|escape:'html'}">{$r->text.root|lower}</a></td>
            <td>{$r->text.access|lower}</td>
            <td>{$r->text.edit|lower}</td>
            <td {if $sort == 'ts'}class="selected"{/if}><a href="{$ts_sort_url|escape:'html'}">{$r->text.last_active|lower}</a></td>
        </tr>
    </thead>
    <tbody>

    {foreach from=$r->ulist item=foo}

    <tr style="background-color:{cycle values="#ffffff,#eeeeee"}">
        <td style="text-align:left;"><a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></td>
        <td>{if $foo.banned}x{/if}</td>
        <td>{if $foo.root}x{/if}</td>
        <td style="text-align:left;">{$r->getAccessLevels($foo.users_id)}</td>
        <td>
            <a href="{$r->makeUrl('/user/edit')}/{$foo.nickname}">{$r->text.profile|lower}</a> |
            <a href="{$r->makeUrl('/admin/access')}/{$foo.nickname}">{$r->text.access|lower}</a>
        </td>
        <td><a href="{$r->makeUrl('/admin/log')}/{$foo.nickname}">{$foo.last_active}</a></td>

    </tr>

    {/foreach}

    </tbody>
    </table>

    {$r->text.pager}

    </fieldset>

    <p><a href="{$r->makeUrl('/home')}">{$r->text.back_2} &raquo;</a></p>

    </div>

</div>

{include file=$r->xhtml_footer}