{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->gtext.photos}" href="{$r->makeUrl('/photos/rss', null, true)}" />

    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>

    {literal}
    <script type="text/javascript">
    // <![CDATA[
    function trunc(truncateMe) {
        var len = 80;
        var p = $(truncateMe);
        if (p) {
            var trunc = p.innerHTML.stripTags();
            if (trunc.length > len) {

                // Truncate the content of the P, then go back to the end of the
                // previous word to ensure that we don't truncate in the middle of
                // a word

                trunc = trunc.substring(0, len);
                trunc = trunc.replace(/\w+$/, '');

                // Add an ellipses to the end and make it a link that expands
                // the paragraph back to its original size

                trunc += ' <a href="#" ' +
                'onclick="this.parentNode.innerHTML=' +
                'unescape(\''+escape(p.innerHTML)+'\');return false;">' +
                '...<\/a>';

                p.innerHTML = trunc;

            }
        }
    }
    // ]]>
    </script>

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

                <h1>{$r->gtext.header|lower}</h1>
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

            {if $r->arr.photos}
            {foreach from=$r->arr.photos item=foo name=bar}
            {if $smarty.foreach.bar.iteration % 2 != 0}

                {capture name=album}
                    <p>
                    {$foo.published_on}<br />
                    {$r->gtext.publisher}: <a href="{$r->makeURL('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a><br />
                    {$r->countPhotos($foo.id)} {$r->gtext.photos}
                    </p>
                    <div id="truncId{$foo.id}">{$foo.body_html}</div>
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

                {literal}
                <script type="text/javascript">
                // <![CDATA[
                trunc('truncId{/literal}{$foo.id}{literal}');
                // ]]>
                </script>
                {/literal}

            {/if}
            {/foreach}
            {else}
                <p>{$r->gtext.no_photos}</p>
            {/if}

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {if $r->arr.photos}
            {foreach from=$r->arr.photos item=foo name=bar}
            {if $smarty.foreach.bar.iteration % 2 == 0}

                {capture name=album}
                    <p>
                    {$foo.published_on}<br />
                    {$r->gtext.publisher}: <a href="{$r->makeURL('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a><br />
                    {$r->countPhotos($foo.id)} {$r->gtext.photos}
                    </p>
                    <div id="truncId{$foo.id}">{$foo.body_html}</div>
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

                {literal}
                <script type="text/javascript">
                // <![CDATA[
                trunc('truncId{/literal}{$foo.id}{literal}');
                // ]]>
                </script>
                {/literal}

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