{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->text.photos}" href="{$r->makeUrl('/photos/rss', null, true)}" />

    {literal}
    <style type="text/css">
        #leftside { width: 468px; margin-left: 2px; margin-top: 0px; }
        #rightside { width: 468px; margin-top: 0px; }
    </style>
    {/literal}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer">
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">

                <h1>{$r->text.header|lower}</h1>
                {insert name="userInfo"}
                {$r->navlist()}
                <div class="clearboth"></div>
                {insert name="editLinks" div=true}
			</div>

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
                    {$r->text.publisher}: <a href="{$r->makeURL('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a><br />
                    {$r->countPhotos($foo.id)} {$r->text.photos}
                    </p>
                    {$foo.body_html}
                    <div class="clearboth"></div>
                    {insert name="editLinks2" album_id=$foo.id div=true}
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
                    {$r->text.publisher}: <a href="{$r->makeURL('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a><br />
                    {$r->countPhotos($foo.id)} {$r->text.photos}
                    </p>
                    {$foo.body_html}
                    <div class="clearboth"></div>
                    {insert name="editLinks2" album_id=$foo.id div=true}
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
           {$r->text.pager}
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