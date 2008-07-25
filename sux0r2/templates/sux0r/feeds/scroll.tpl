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

                <div id="feeds">
                <p>{$r->text.feeds}</p>
                <ul>
                    {if $r->feeds()}
                    {foreach from=$r->feeds() item=foo}
                        <li><a href="{$r->makeUrl('/feeds')}/{$foo.id}">{$foo.title}</a></li>
                    {/foreach}
                    {/if}
                    <li><em><a href="{$r->makeUrl('/feeds/suggest')}">{$r->text.suggest} &raquo;</a></em></li>
                </ul>
                </div>


			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            <!-- Category filters -->
            {insert name="bayesFilters" form_url=$r->text.form_url}

            {* Feeds *}
            {if $r->fp}
            {foreach from=$r->fp item=foo}

                {capture name=feed}

                    <!-- Content -->
                    <p><em>{$foo.published_on}</em></p>
                    <div class="rssItem">{$foo.body_html}</div>
                    <div class="clearboth"></div>

                    <!-- Read more -->
                    <p><a href="{$foo.url}">Read more &raquo;</a></p>

                    <!-- Naive Baysian Classification -->
                    <div class="categoryContainer">
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {$r->genericBayesInterface($foo.id, 'rss', 'feeds', $smarty.capture.document)}
                    </div>

                {/capture}

                {$r->widget($foo.title, $smarty.capture.feed, $foo.url)}


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