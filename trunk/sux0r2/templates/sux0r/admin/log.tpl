{capture name=header}

    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>

{literal}
<script type="text/javascript">
// <![CDATA[
// Toggle subscription to a feed
function togglePrivate(log_id) {

    var url = '{/literal}{$r->url}/modules/admin/toggle.php{literal}';
    var pars = { id: log_id };

    new Ajax.Request(url, {
            method: 'post',
            parameters: pars,
            onSuccess: function(transport) {
                // Toggle images
                var myImage = transport.responseText.strip();
                var myClass = 'img.private' + log_id;
                var res = $$(myClass);
                for (i = 0; i < res.length; i++) {
                    res[i].src = '{/literal}{$r->url}/media/{$r->partition}/assets/{literal}' + myImage;
                }
            },
            onFailure: function(transport){
                if (transport.responseText.strip())
                    alert(transport.responseText);
            }
    });

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

    <a href="{$r->makeUrl('/admin')}">Back to adminstration &raquo;</a>
    <p />

    <fieldset>
    <legend>Log{if $nickname}: {$nickname}{/if}</legend>

    <table class="adminTable">
    <thead>
        <tr>
            <td>user</td>
            <td>log</td>
            <td {if $sort == 'ts'}class="selected"{/if}><a href="{$ts_sort_url}">timestamp</a></td>
            <td>private</td>
        </tr>
    </thead>
    <tbody>

    {foreach from=$r->ulog item=foo}

    <tr>
        <td style="text-align:left;"><a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></td>
        <td style="text-align:left;">{$foo.body_html}</td>
        <td>{$foo.ts}</td>
        <td>
            <img
            src="{$r->url}/media/{$r->partition}/assets/{if $foo.private}lock1.gif{else}lock2.gif{/if}"
            onclick="togglePrivate('{$foo.id}');"
            style='cursor: pointer;'
            class='private{$foo.id}'
            alt="" />
        </td>
    </tr>

    {/foreach}

    </tbody>
    </table>

    <p>{$r->text.pager}</p>

    </fieldset>

    </div>

</div>

{include file=$r->xhtml_footer}