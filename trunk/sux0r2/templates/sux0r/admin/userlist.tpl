{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

    <div id="header">
        <h1>sux0r - it sux0rs up all the web</h1>
        {insert name="userInfo"}
        {$r->navlist()}
    </div>

    <div id="middle">

    Lorem ipsum dolor sit amet, consec tetuer Lorem ipsum dolor sit amet
    <p />

    <fieldset>
    <legend>Administration</legend>

    <table class="adminTable">
    <thead>
        <tr>
            <td {if $sort == 'nickname'}class="selected"{/if}><a href="{$nickname_sort_url}">username</a></td>
            <td {if $sort == 'banned'}class="selected"{/if}><a href="{$banned_sort_url}">banned</a></td>
            <td {if $sort == 'root'}class="selected"{/if}><a a href="{$root_sort_url}">root</a></td>
            <td>access</td>
            <td>edit</td>
            <td {if $sort == 'ts'}class="selected"{/if}><a href="{$ts_sort_url}">last active</a></td>


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
            <a href="{$r->makeUrl('/user/edit')}/{$foo.nickname}">profile</a> |
            <a href="{$r->makeUrl('/admin/access')}/{$foo.nickname}">access</a>
        </td>
        <td><a href="{$r->makeUrl('/admin/log')}/{$foo.nickname}">{$foo.last_active}</a></td>

    </tr>

    {/foreach}

    </tbody>
    </table>

    <p>{$r->text.pager}</p>

    </fieldset>

    </div>

</div>

{include file=$r->xhtml_footer}