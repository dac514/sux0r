{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer" >
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">

                <h1>sux0r - it sux0rs up all the web</h1>
                {insert name="userInfo"}
                {$r->navlist()}

			</div>
		</td>
	</tr>
	<tr>
        <td style="vertical-align:top;">
			<div id="leftside">

            {* Bookmarks *}
            {if $r->fp}
            {foreach from=$r->fp item=foo}

                <div style="border: 1px dashed #ccc; padding: 10px; margin: 10px;">
                    <a href="{$foo.url}">{$foo.title}</a>
                    <div>{$foo.body_html}</div>
                    <em>Published on: {$foo.published_on}</em><br />
                    {$r->tags($foo.id)}
                </div>


            {/foreach}
            {else}
                <div style="border: 1px dashed #ccc; padding: 10px; margin: 10px;">
                Not found.
                </div>
            {/if}

            {$r->text.pager}


			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {if $r->fp}
            <ul>
                <li><a href="{$datesort_url}">Sort by date</a></li>
                <li><a href="{$alphasort_url}">Sort alphabetically</a></li>
                <li><em><a href="{$r->makeUrl('/bookmarks/suggest')}">Suggest a bookmark &raquo;</a></em></li>
            </ul>
            {/if}


			</div>
		</td>
	</tr>
	<tr>
		<td colspan="2" style="vertical-align:bottom;">
			<div id="footer">
			Footer
			</div>
		</td>
	</tr>
</table>

{include file=$r->xhtml_footer}