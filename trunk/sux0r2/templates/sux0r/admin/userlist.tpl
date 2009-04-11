{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    <h1>{$r->gtext.admin|lower}</h1>
    {insert name="userInfo"}
    {insert name="navlist"}
    <div class='clearboth'></div>
</div>

    <div id="middle">

    <fieldset>

    <table class="adminTable">
    <thead>
        <tr>
            <td {if $sort == 'nickname'}class="selected"{/if}><a href="{$r->text.sort_url|escape:'html'}&amp;sort=nickname">{$r->gtext.nickname|lower}</a></td>
            <td {if $sort == 'banned'}class="selected"{/if}><a href="{$r->text.sort_url|escape:'html'}&amp;sort=banned">{$r->gtext.banned|lower}</a></td>
            <td {if $sort == 'root'}class="selected"{/if}><a href="{$r->text.sort_url|escape:'html'}&amp;sort=root">{$r->gtext.root|lower}</a></td>
            <td>{$r->gtext.access|lower}</td>
            <td>{$r->gtext.edit|lower}</td>
            <td {if $sort == 'ts'}class="selected"{/if}><a href="{$r->text.sort_url|escape:'html'}&amp;sort=ts">{$r->gtext.last_active|lower}</a></td>
        </tr>
    </thead>
    <tbody>

    {foreach from=$r->arr.ulist item=foo}

    <tr style="background-color:{cycle values="#ffffff,#eeeeee"}">
        <td style="text-align:left;"><a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></td>
        <td>{if $foo.banned}x{/if}</td>
        <td>{if $foo.root}x{/if}</td>
        <td style="text-align:left;">{$r->getAccessLevels($foo.users_id)}</td>
        <td>
            <a href="{$r->makeUrl('/user/edit')}/{$foo.nickname}">{$r->gtext.profile|lower}</a> |
            <a href="{$r->makeUrl('/admin/access')}/{$foo.nickname}">{$r->gtext.access|lower}</a>
        </td>
        <td><a href="{$r->makeUrl('/admin/log')}/{$foo.nickname}">{$foo.last_active}</a></td>

    </tr>

    {/foreach}

    </tbody>
    </table>

    {$r->text.pager}

    </fieldset>

    <p><a href="{$r->makeUrl('/home')}">{$r->gtext.back_2} &raquo;</a></p>

    </div>

</div>

{include file=$r->xhtml_footer}