{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->text.photos}" href="{$r->makeUrl('/photos/rss', null, true)}" />

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
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">
			<div id="leftside">

            {capture name=editLinks}{insert name="editLinks" br=true}{insert name="editLinks2" album_id=$r->album.id br=true}{/capture}
            {if $smarty.capture.editLinks}<div class="editLinks">{$smarty.capture.editLinks}</div>{/if}

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

                <div class="widget">
                    <h2><a href="{$r->makeUrl('/photos/album')}/{$r->album.id}">{$r->album.title}</a></h2>

                    <div class="photoAlbumItem">

                    {if $r->pho}
                    <div class="thumbnailLinks">
                    {foreach from=$r->pho item=foo name=bar}

                       {strip}
                       <a href="{$r->makeUrl('/photos/view')}/{$foo.id}">
                       <img class="thumbnail" src="{$r->url}/data/photos/{$foo.image}" alt="" width="{#thumbnailWidth#}" height="{#thumbnailHeight#}" />
                       </a>
                       {/strip}

                    {/foreach}
                    </div>
                    {else}
                        {$r->text.no_photos}...
                    {/if}

                    <div class="clearboth"></div>

                    </div>

                    {$r->text.pager}


                    <div class="clearboth"></div>
                    <b class="bb"><b></b></b>

                </div>



			</div>
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