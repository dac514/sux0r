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

{* Header *}
<div id="header">
    {insert name="userInfo"}
    <div class='clearboth'></div>
</div>

    <div id="middle">

    <fieldset>
    <legend>{$r->gtext.admin}</legend>

    <form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
    <input type="hidden" name="token" value="{$token}" />

    <input type="hidden" name="nickname" value="{$nickname}" />
    <input type="hidden" name="users_id" value="{$users_id}" />
    <input type="hidden" name="integrity" value="{$r->integrityHash($users_id, $nickname)}" />
    {validate id="integrity" message="integrity failure"}

    <ul id="adminMenu">
    <li><a href="{$r->makeUrl('/bookmarks/edit')}">{$r->gtext.new}</a></li>
    </ul>

    <table class="adminTable">
    <thead>
        <tr>
            <td>{$r->gtext.title|lower}</td>
            <td>{$r->gtext.published_on|lower}</td>
            <td>{$r->gtext.draft|lower}</td>
            <td>{$r->gtext.suggested|lower}</td>
            <td>{$r->gtext.delete|lower}</td>
        </tr>
    </thead>
    <tbody>

    {foreach from=$r->arr.bookmarks item=foo}

    <tr style="background-color:{cycle values="#ffffff,#eeeeee"}">
        <td style="text-align: left;"><a href="{$r->makeUrl('/bookmarks/edit')}/{$foo.id}">{$foo.title}</a></td>
        <td>{$foo.published_on}</td>
        <td>{if $foo.draft}x{/if}</td>
        <td><a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></td>
        <td><input type="checkbox" name="delete[{$foo.id}]" value="1" /></td>
    </tr>

    {/foreach}

    </tbody>
    </table>

    {$r->text.pager}

    <p><br />
    <input type="button" class="button" value="{$r->gtext.delete}" onclick="rm('default', '{$r->gtext.alert_delete}');" />
    </p>

    </form>

    </fieldset>

    <p><a href="{$r->makeUrl('/bookmarks')}">{$r->gtext.back_2} &raquo;</a></p>

    </div>

</div>

{include file=$r->xhtml_footer}