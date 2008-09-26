{capture name=header}

    {if $r->isLoggedIn()}
        {$r->genericBayesInterfaceInit()}
    {else}
        <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>
    {/if}

    {literal}
    <script type="text/javascript">
    // <![CDATA[
    // Set the maximum width of an image
    function maximumWidth(myId, maxW) {
        var pix = document.getElementById(myId).getElementsByTagName('img');
        for (i = 0; i < pix.length; i++) {
            w = pix[i].width;
            h = pix[i].height;
            if (w > maxW) {
                f = 1 - ((w - maxW) / w);
                pix[i].width = w * f;
                pix[i].height = h * f;
            }
        }
    }
    Event.observe(window, 'load', function() {
        maximumWidth('suxBlog', {/literal}{#maxPhotoWidth#}{literal});
    });
    // ]]>
    </script>
    {/literal}

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


                {if $r->sidelist}
                <div class="sidelist">
                <p>{$r->text.sidelist}</p>
                <ul>
                    {foreach from=$r->sidelist item=foo}
                        <li><a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$foo.title}</a></li>
                    {/foreach}
                </ul>
                </div>
                {/if}


                {if $r->archives()}
                <div class="archives">
                <p>{$r->text.archives}</p>
                <ul>
                {foreach from=$r->archives() item=foo}
                    {capture name=date assign=date}{$foo.year}-{$foo.month}-01{/capture}
                    <li><a href="{$r->makeUrl('/blog/month')}/{$date|date_format:"%Y-%m-%d"}">{$date|date_format:"%B %Y"}</a> ({$foo.count})</li>
                {/foreach}
                </ul>
                </div>
                {/if}

                {if $r->authors()}
                <div class="authors">
                <p>{$r->text.authors}</p>
                <ul>
                {foreach from=$r->authors() item=foo}
                    <li><a href="{$r->makeUrl('/blog/author')}/{$foo.nickname}">{$foo.nickname}</a> ({$foo.count})</li>
                {/foreach}
                </ul>
                </div>
                {/if}

                <ul>
                <li><a href="{$r->makeUrl('/blog/tag/cloud')}">{$r->text.tag_cloud}</a></li>
                </ul>


			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {* ------------------------------------------------------------------------------------------------------ *}

            {* Blogs *}
            {if $r->fp}
            {foreach from=$r->fp item=foo}

                {capture name=blog_url}
                    {$r->makeUrl('/blog/view', null, true)}/{$foo.thread_id}
                {/capture}

                {capture name=blog_img}
                    {if $foo.image}{$r->makeUrl('/', null, true)}/data/blog/{$foo.image}{/if}
                {/capture}

                {capture name=blog}

                    <!-- Content -->
                    <p>{$r->text.by} <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a> <em>{$r->text.on|lower} {$foo.published_on}</em></p>
                    <p>{$foo.body_html}</p>
                    <div class="clearboth"></div>

                    <!-- Reply -->
                    <p>
                    <a href="{$r->makeUrl('/blog/reply')}/{$foo.id}">{$r->text.reply}</a>
                    {$r->tags($foo.id)}
                    </p>

                    <!-- Flair -->
                    {capture name=url assign=url}{$r->makeUrl('/blog/view', null, true)}/{$foo.thread_id}{/capture}
                    <div class="flair"><p>
                    <a href='http://slashdot.org/slashdot-it.pl?op=basic&url={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/slashdot.gif' alt='Slashdot' width='16' height='16' /></a>
                    <a href='http://digg.com/submit?url={$url|escape:'url, UTF-8'}&title={$foo.title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/digg.gif' alt='Digg' width='16' height='16' /></a>
                    <a href='http://www.facebook.com/share.php?u={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/facebook.gif' alt='Facebook' width='16' height='16' /></a>
                    <a href='http://www.myspace.com/index.cfm?fuseaction=postto&t={$foo.title|escape:'url, UTF-8'}&c=&u={$url|escape:'url, UTF-8'}&l=' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/myspace.gif' alt='Myspace' width='16' height='16' /></a>
                    <a href='http://www.stumbleupon.com/submit?url={$url|escape:'url, UTF-8'}&title={$foo.title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/stumbleupon.gif' alt='StumbleUpon' width='16' height='16' /></a>
                    <a href='http://del.icio.us/login/?url={$url|escape:'url, UTF-8'}&title={$foo.title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/delicious.gif' alt='Del.icio.us' width='16' height='16' /></a>
                    </p></div>


                    <!-- Naive Baysian Classification -->
                    <div class="categoryContainer">
                        {$r->authorCategories($foo.id, $foo.users_id)}
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {$r->genericBayesInterface($foo.id, 'messages', 'blog', $smarty.capture.document)}
                    </div>

                    {if $r->isLoggedIn()}{insert name="edit" id=$foo.id}{/if}

                {/capture}

                <div id="suxBlog">
                {$r->widget($foo.title, $smarty.capture.blog, $smarty.capture.blog_url, $smarty.capture.blog_img)}
                </div>




            {/foreach}
            {else}
                <p>{$r->text.not_found}</p>
            {/if}

            {* ------------------------------------------------------------------------------------------------------ *}

            <a name="comments"></a>
            {* Comments *}
            {if $r->comments}
            {foreach from=$r->comments item=foo}

                <div class="comment" id="suxComment{$foo.id}" style="margin-left:{$r->indenter($foo.level)}px;">
                <a name="comment-{$foo.id}"></a>
                <!-- Content -->
                <p><strong>{$foo.title}</strong> {$r->text.by|lower} <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a> {$r->text.on|lower} {$foo.published_on}</p>
                <p>{$foo.body_html}</p>
                <p><a href="{$r->makeUrl('/blog/reply')}/{$foo.id}">{$r->text.reply}</a></p>
                {if $r->isLoggedIn()}{insert name="edit" id=$foo.id}{/if}
                </div>

                {literal}
                <script type="text/javascript">
                // <![CDATA[
                // Set the maximum width of an image
                Event.observe(window, 'load', function() {
                        maximumWidth({/literal}'suxComment{$foo.id}', {math equation="x - y" x=#maxPhotoWidth# y=$r->indenter($foo.level)}{literal});
                });
                // ]]>
                </script>
                {/literal}

            {/foreach}
            {else}
                <p>{$r->text.no_comments}</p>
            {/if}

            {* ------------------------------------------------------------------------------------------------------ *}

            {$r->text.pager}

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