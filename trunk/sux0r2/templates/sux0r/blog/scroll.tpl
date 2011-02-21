{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->gtext.blog}" href="{$r->makeUrl('/blog/rss', null, true)}" />

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

                <h1>{$r->gtext.header|lower}</h1>
                {insert name="userInfo"}
                {insert name="navlist"}

            </div>
        </td>
    </tr>
    <tr>
        <td style="vertical-align:top;">
            <div id="leftside">

                {if $r->arr.sidelist}
                <div class="sidelist">
                <p class="sideListTitle">{$r->text.sidelist}</p>
                <ul>
                    {foreach from=$r->arr.sidelist item=foo}
                        <li><a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$foo.title}</a></li>
                    {/foreach}
                </ul>
                </div>
                {/if}


                {if $r->archives()}
                <div class="archives">
                <p>{$r->gtext.archives}</p>
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
                <p>{$r->gtext.authors}</p>
                <ul>
                {foreach from=$r->authors() item=foo}
                    <li><a href="{$r->makeUrl('/blog/author')}/{$foo.nickname}">{$foo.nickname}</a> ({$foo.count})</li>
                {/foreach}
                </ul>
                </div>
                {/if}

                <ul>
                <li><a href="{$r->makeUrl('/blog/tag/cloud')}">{$r->gtext.tag_cloud}</a></li>
                </ul>

                {if $r->recent() && !$r->arr.sidelist}
                <div class="recent">
                <p>{$r->gtext.recent_comments} :</p>
                {foreach from=$r->recent() item=foo}
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}#comment-{$foo.id}">{$foo.title}</a>
                    {$r->gtext.by|lower} <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a>
                    {$r->gtext.in|lower} <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$foo.title_fp}</a>
                    {$r->gtext.on|lower} {$foo.published_on}<br /><br />
                {/foreach}
                </div>
                {/if}


            </div>
        </td>
        <td style="vertical-align:top;">
            <div id="rightside">

            <!-- Category filters -->
            {insert name="bayesFilters" form_url=$r->text.form_url}

            {* Blogs *}
            {if $r->arr.fp}
            {foreach from=$r->arr.fp item=foo}

                {capture name=blog_url}
                    {$r->makeUrl('/blog/view', null, true)}/{$foo.thread_id}
                {/capture}

                {capture name=blog_img}
                    {if $foo.image}{$r->myHttpServer()}{$r->url}/data/blog/{$foo.image|escape:'url'}{/if}
                {/capture}

                {capture name=blog}

                    <!-- Content -->
                    <p>{$r->gtext.by} <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a> <em>{$r->gtext.on|lower} {$foo.published_on}</em></p>
                    <div class="blogItem">{insert name="highlight" html=$foo.body_html}</div>


                    <!-- Permanlink, Comments -->
                    <p>
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$r->gtext.p_link}</a>,
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}#comments">{$r->gtext.comments} ({$foo.comments})</a>
                    {$r->tags($foo.id)}
                    </p>


                    <!-- Flair -->
                    {capture name=url assign=url}{$r->makeUrl('/blog/view', null, true)}/{$foo.thread_id}{/capture}
                    <div class="flair"><p>
                    <a href='http://slashdot.org/slashdot-it.pl?op=basic&amp;url={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/assets/slashdot.gif' alt='Slashdot' width='16' height='16' /></a>
                    <a href='http://digg.com/submit?url={$url|escape:'url, UTF-8'}&amp;title={$foo.title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/assets/digg.gif' alt='Digg' width='16' height='16' /></a>
                    <a href='http://www.facebook.com/share.php?u={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/assets/facebook.gif' alt='Facebook' width='16' height='16' /></a>
                    <a href='http://www.myspace.com/index.cfm?fuseaction=postto&amp;t={$foo.title|escape:'url, UTF-8'}&amp;c=&amp;u={$url|escape:'url, UTF-8'}&amp;l=' target='_blank' ><img src='{$r->url}/media/{$r->partition}/assets/myspace.gif' alt='Myspace' width='16' height='16' /></a>
                    <a href='http://www.stumbleupon.com/submit?url={$url|escape:'url, UTF-8'}&amp;title={$foo.title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/assets/stumbleupon.gif' alt='StumbleUpon' width='16' height='16' /></a>
                    <a href='http://del.icio.us/login/?url={$url|escape:'url, UTF-8'}&amp;title={$foo.title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/assets/delicious.gif' alt='Del.icio.us' width='16' height='16' /></a>
                    </p></div>

                    {capture name=nbc}{strip}
                        {$r->authorCategories($foo.id, $foo.users_id)}
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {$r->genericBayesInterface($foo.id, 'messages', 'blog', $smarty.capture.document)}
                    {/strip}{/capture}
                    {if $smarty.capture.nbc}
                        <!-- Naive Baysian Classification -->
                        <div class="categoryContainer">
                        {$smarty.capture.nbc}
                        </div>
                    {/if}

                    {if $r->isLoggedIn()}{insert name="edit" id=$foo.id}{/if}

                {/capture}

                {capture name=title_HL}{insert name="highlight" html=$foo.title}{/capture}
                {$r->widget($smarty.capture.title_HL, $smarty.capture.blog, $smarty.capture.blog_url, $smarty.capture.blog_img)}


            {/foreach}
            {else}
                <p>{$r->gtext.not_found}</p>
            {/if}

            {$r->text.pager}

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

{insert name="bayesFilterScript"}

{include file=$r->xhtml_footer}