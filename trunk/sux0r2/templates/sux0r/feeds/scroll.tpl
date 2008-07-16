{capture name=header}

{$r->genericBayesInterfaceInit()}

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

                <p>Todo</p>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            <!-- Category filters -->
            {insert name="bayesFilters" form_url=$r->text.form_url}

            {* Feeds *}
            {if $r->fp}
            {foreach from=$r->fp item=foo}

                {capture name=blog}

                    <!-- Content -->
                    <p>{$foo.published_on}, <a href="{$r->makeUrl('/user/profile')}/{$foo.nickname}">{$foo.nickname}</a></p>
                    <p>{$foo.body_html}</p>
                    <div class="clearboth"></div>

                    <!-- Read more -->
                    <p><a href="{$r->makeUrl('/blog/view')}/{$foo.thread_id}">Read more...</a></p>


                    <!-- Naive Baysian Classification -->
                    <div class="categoryContainer">
                        {$r->genericBayesInterface($foo.id, 'messages', 'feeds', $foo.body_plaintext)}
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