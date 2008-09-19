{capture name=header}

    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>

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

    <table border="1" width="100%">
    <thead>
        <tr>
            <td>username</td>
            <td>last active</td>
            <td>root</td>
            <td>banned</td>
            <td>edit access</td>
        </tr>
    </thead>

    {foreach from=$r->ulist item=foo}

    <tr>
        <td><a href="{$r->makeUrl('/user/edit')}/{$foo.nickname}">{$foo.nickname}</a></td>
        <td><a href="{$r->makeUrl('/admin/log')}/{$foo.nickname}">{$foo.last_active}</a></td>
        <td>{if $foo.root}x{/if}</td>
        <td>{if $foo.banned}x{/if}</td>
        <td><a href="{$r->makeUrl('/admin/access')}/{$foo.nickname}">edit</a></td> {*TODO: Ajax tooltip to see user access *}
    </tr>

    {/foreach}

    </table>

    <p>{$r->text.pager}</p>

    </fieldset>

    </div>

</div>

{include file=$r->xhtml_footer}