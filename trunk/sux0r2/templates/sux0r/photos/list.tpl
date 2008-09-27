{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->text.photos}" href="{$r->makeUrl('/photos/rss', null, true)}" />

    {literal}
    <style type="text/css">
        #leftside { width: 468px; margin-left: 2px; }
        #rightside { width: 468px; }
    </style>
    {/literal}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer">
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">

                <h1>sux0r - it sux0rs up all the web</h1>
                {insert name="userInfo"}
                {$r->navlist()}

			</div>
            <div class="clearboth"></div>

            <div>{insert name="editLinks"}</div>
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">

			<div id="leftside">

            {if $r->pho}
            {foreach from=$r->pho item=foo name=bar}
            {if $smarty.foreach.bar.iteration % 2 != 0}

                {capture name=album}
                    <p>
                    {$foo.published_on}<br />
                    {$foo.title}<br />
                    {$r->countPhotos($foo.id)} {$r->text.photos}
                    </p>
                    {$foo.body_html}
                    <p>{insert name="editLinks2" album_id=$foo.id}</p>
                {/capture}

                {capture name=album_url}
                    {$r->makeUrl('/photos/album', null, true)}/{$foo.id}
                {/capture}

                {capture name=thumbnail}
                    {$r->getThumbnail($foo.id)}
                {/capture}

                {$r->widget($foo.title, $smarty.capture.album, $smarty.capture.album_url, $smarty.capture.thumbnail, null, null, 'floatleft')}

            {/if}
            {/foreach}
            {/if}

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {if $r->pho}
            {foreach from=$r->pho item=foo name=bar}
            {if $smarty.foreach.bar.iteration % 2 == 0}

                {capture name=album}
                    <p>
                    {$foo.published_on}<br />
                    {$foo.title}<br />
                    {$r->countPhotos($foo.id)} {$r->text.photos}
                    </p>
                    {$foo.body_html}
                    <p>{insert name="editLinks2" album_id=$foo.id}</p>
                {/capture}

                {capture name=album_url}
                    {$r->makeUrl('/photos/album', null, true)}/{$foo.id}
                {/capture}

                {capture name=thumbnail}
                    {$r->getThumbnail($foo.id)}
                {/capture}

                {$r->widget($foo.title, $smarty.capture.album, $smarty.capture.album_url, $smarty.capture.thumbnail, null, null, 'floatleft')}

            {/if}
            {/foreach}
            {/if}

			</div>
		</td>
	</tr>
    <tr>
        <td colspan="2" style="text-align:center;">
           <p>{$r->text.pager}</p>
        </td>
    </tr>
	<tr>
		<td colspan="2" style="vertical-align:bottom;">
			<div id="footer">
			{$r->copyright()}
			</div>
		</td>
	</tr>
</table>


{include file=$r->xhtml_footer}