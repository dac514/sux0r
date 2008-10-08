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

                <h1>{$r->text.header|lower}</h1>
                {insert name="userInfo"}
                {$r->navlist()}

			</div>
            <div class="clearboth"></div>
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">
			<div id="leftside">

            <div class="editLinks">
            <p>{$r->text.publisher}: <a href="{$r->makeURL('/user/profile')}/{$r->album.nickname}">{$r->album.nickname}</a></p>  
            {insert name="editLinks" br=true}
            {insert name="editLinks2" album_id=$r->album.id br=true}
            </div>
            
            {$r->album.body_html}

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
                       <img class="thumbnail" src="{$r->url}/data/photos/{$foo.image|escape:'url'}" alt="" width="{#thumbnailWidth#}" height="{#thumbnailHeight#}" />
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