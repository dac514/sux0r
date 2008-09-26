{capture name=header}

    {literal}
    <script type='text/javascript'>
    // <![CDATA[
    function rm(myForm, myWarning) {
        if(confirm(myWarning)) {
            var x = document.getElementsByName(myForm);
            x[0].submit();
        }
    }
    // ]]>
    </script>
    {/literal}

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

    <fieldset>
    <legend>{$r->text.admin}</legend>

    <form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
    <input type="hidden" name="token" value="{$token}" />

    <input type="hidden" name="nickname" value="{$nickname}" />
    <input type="hidden" name="users_id" value="{$users_id}" />
    <input type="hidden" name="integrity" value="{$r->integrityHash($users_id, $nickname)}" />
    {validate id="integrity" message="integrity failure"}

    <table class="adminTable">
    <thead>
        <tr>
            <td>{$r->text.title|lower}</td>
            <td>{$r->text.published|lower}</td>
            <td>{$r->text.photos|lower}</td>
            <td>{$r->text.draft|lower}</td>
            <td>{$r->text.publisher|lower}</td>
            <td>{$r->text.delete|lower}</td>
        </tr>
    </thead>
    <tbody>

    {foreach from=$r->fp item=foo}

        <tr style="background-color:{cycle values="#ffffff,#eeeeee"}">
            <td style="text-align: left;"><a href="{$r->makeUrl('/photos/album/edit')}/{$foo.id}">{$foo.title}</a></td>
            <td>{$foo.published_on}</td>
            <td>{if $foo.photos_count}{$foo.photos_count}{/if}</td>
            <td>{if $foo.draft}x{/if}</td>
            <td><a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></td>
            <td><input type="checkbox" name="delete[{$foo.id}]" value="1" /></td>
        </tr>

    {/foreach}

    </tbody>
    </table>

    <p>{$r->text.pager}</p>

    <p>
    <input type="button" class="button" value="{$r->text.delete}" onclick="rm('default', '{$r->text.alert_delete}');" />
    </p>

    </form>

    </fieldset>

    </div>

</div>

{include file=$r->xhtml_footer}