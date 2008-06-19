{include file=$r->xhtml_header}

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

            <p>
            Current month:<br />
            {foreach from=$r->articles() item=foo}
                <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">{$foo.title}</a><br /><br />
            {/foreach}
            </p>

            <p>
            Most recent comments:<br />
            {foreach from=$r->recent() item=foo}
                <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}#{$foo.id}">{$foo.title}</a>
                by <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a>
                in <strong>{$foo.title_fp}</strong> on {$foo.published_on}<br /><br />
            {/foreach}
            </p>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            <p>
            Author:
            [ Dropdown ]
            Tags:
            [ Dropdown ]
            Month/Year:
            [ Dropdown ]
            [ Go! ]
            </p>


            {* Blog widgets *}
            {foreach from=$r->articles() item=foo}

                {capture name=blog_url}
                    {$r->makeUrl('/blog/view', null, true)}/{$foo.thread_id}
                {/capture}

                {capture name=blog_img}
                    {if $foo.image}{$r->makeUrl('/', null, true)}/data/blog/{$foo.image}{/if}
                {/capture}

                {capture name=blog}

                    <!-- Content -->
                    <p>{$foo.published_on}, <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a>

                    {* Find all bayes categories/tags associated to this document by author
                    Optionally, find all bayes categories/tags associated to this document. *}

                    {$r->tags($foo.category_id, $foo.users_id)}

                    </p>

                    <p>{$foo.body_html}</p>

                    <div class="clearboth"></div>

                    <!-- Permanlink, Comments -->
                    <p><a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">Permanent Link</a>,
                    <a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}#comments">Comments ({$foo.comments})</a></p>

                    <!-- Flair -->
                    <div class="flair"><p>
                    <a href='http://slashdot.org/slashdot-it.pl?op=basic&url={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/slashdot.gif' alt='Slashdot' width='16' height='16' /></a>
                    <a href='http://digg.com/submit?url={$url|escape:'url, UTF-8'}&title={$title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/digg.gif' alt='Digg' width='16' height='16' /></a>
                    <a href='http://www.facebook.com/share.php?u={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/facebook.gif' alt='Facebook' width='16' height='16' /></a>
                    <a href='http://www.myspace.com/index.cfm?fuseaction=postto&t={$title|escape:'url, UTF-8'}&c=&u={$url|escape:'url, UTF-8'}&l=' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/myspace.gif' alt='Myspace' width='16' height='16' /></a>
                    <a href='http://www.stumbleupon.com/submit?url={$url|escape:'url, UTF-8'}&title={$title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/stumbleupon.gif' alt='StumbleUpon' width='16' height='16' /></a>
                    <a href='http://del.icio.us/login/?url={$url|escape:'url, UTF-8'}&title={$title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/delicious.gif' alt='Del.icio.us' width='16' height='16' /></a>
                    </p></div>

                    <!-- Bayesian tags -->
                    {capture name=tags}
                        {foreach from=$r->getUserVectors() key=k item=v}
                        {$v}: <span class="htmlSelect">{html_options name='category_id[]' options=$r->getCategoriesByVector($k) selected=$foo.category_id}</span>
                        {/foreach}
                    {/capture}

                    {if $smarty.capture.tags|trim}
                    <p>{$smarty.capture.tags}</p>
                    {if $foo.linked}<p>Linked to: {$foo.linked}</p>{/if}
                    {/if}

                {/capture}

                {$r->widget($foo.title, $smarty.capture.blog, $smarty.capture.blog_url, $smarty.capture.blog_img)}


            {/foreach}



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