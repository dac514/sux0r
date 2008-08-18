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
    maximumWidth('rightside', {/literal}{#maxPhotoWidth#}{literal});
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
                <div id="sidelist">
                <p>{$r->text.sidelist}</p>
                <ul>
                    {foreach from=$r->sidelist item=foo}
                        <li><a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$foo.title}</a></li>
                    {/foreach}
                </ul>
                </div>
                {/if}


                {if $r->archives()}
                <div id="archives">
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
                <div id="archives">
                <p>{$r->text.authors}</p>
                <ul>
                {foreach from=$r->authors() item=foo}
                    <li><a href="{$r->makeUrl('/blog/author')}/{$foo.nickname}">{$foo.nickname}</a> ({$foo.count})</li>
                {/foreach}
                </ul>
                </div>
                {/if}


                {if $r->recent()}
                <div id="recent">
                <p>Most recent comments:</p>
                {foreach from=$r->recent() item=foo}
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}#comment-{$foo.id}">{$foo.title}</a>
                    by <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a>
                    in <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$foo.title_fp}</a>
                    on {$foo.published_on}<br /><br />
                {/foreach}
                </div>
                {/if}

                <ul>
                <li><a href="{$r->makeUrl('/blog/tag/cloud')}">Tag cloud</a></li>
                </ul>



			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            <!-- Category filters -->
            {insert name="bayesFilters" form_url=$r->text.form_url}


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
                    <p>By <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a> <em>on {$foo.published_on}</em></p>
                    <p>{$foo.body_html}</p>
                    <div class="clearboth"></div>


                    <!-- Permanlink, Comments -->
                    <p>
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">Permanent Link</a>,
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}#comments">Comments ({$foo.comments})</a>
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


                    <!-- Tags -->


                    <!-- Naive Baysian Classification -->
                    <div class="categoryContainer">
                        {$r->authorCategories($foo.id, $foo.users_id)}
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {$r->genericBayesInterface($foo.id, 'messages', 'blog', $smarty.capture.document)}
                    </div>

                {/capture}

                {$r->widget($foo.title, $smarty.capture.blog, $smarty.capture.blog_url, $smarty.capture.blog_img)}


            {/foreach}
            {else}
                <p>Not found.</p>
            {/if}

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