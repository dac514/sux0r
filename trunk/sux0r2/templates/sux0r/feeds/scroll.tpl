{capture name=header}

{if $r->isLoggedIn()}
    {$r->genericBayesInterfaceInit()}
{else}
    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>
{/if}

{literal}
<script type="text/javascript">
// <![CDATA[

{/literal}{if $r->isLoggedIn()}{literal}
// Toggle subscription to a feed
function toggleSubscription(feed_id) {

    var url = '{/literal}{$r->url}/modules/feeds/toggle.php{literal}';
    var pars = { id: feed_id };

    new Ajax.Request(url, {
            method: 'post',
            parameters: pars,
            onSuccess: function(transport) {
                // Toggle images
                var myImage = transport.responseText.strip();
                var myClass = 'img.subscription' + feed_id;
                var res = $$(myClass);
                for (i = 0; i < res.length; i++) {
                    res[i].src = '{/literal}{$r->url}/media/{$r->partition}/assets/{literal}' + myImage;
                }
            },
            onFailure: function(transport){
                if (transport.responseText.strip())
                    alert(transport.responseText);
            }
    });

}
{/literal}{/if}{literal}

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
    maximumWidth({/literal}'rightside', {#maxPhotoWidth#}{literal});
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

                <p>{$r->text.feeds}</p>

                {if $r->feeds(true, $users_id)}
                <ul>
                    {foreach from=$r->feeds(true, $users_id) item=foo}
                    <li><a href="{$r->makeUrl('/feeds')}/{$foo.id}">{$foo.title}</a></li>
                    {/foreach}
                </ul>

                {else}

                <ul>
                    {foreach from=$r->feeds(false, $users_id) item=foo}
                    <li><a href="{$r->makeUrl('/feeds')}/{$foo.id}">{$foo.title}</a></li>
                    {/foreach}
                </ul>
                {/if}

                {if $r->isLoggedIn()}
                <p>Actions</p>
                <ul>
                    {insert name="approveLi"}
                    <li><em><a href="{$r->makeUrl('/feeds/manage')}">{$r->text.manage} &raquo;</a></em></li>
                    <li><em><a href="{$r->makeUrl('/feeds/suggest')}">{$r->text.suggest} &raquo;</a></em></li>
                </ul>
                {/if}

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
                    <div style="float:left;margin-right:5px;">
                    {$r->isSubscribed($foo.rss_feeds_id)}
                    </div>
                    <div style="float:left;margin-bottom: 1em;">
                    Feed: {$r->feedLink($foo.rss_feeds_id)}<br />
                    <em>Published on: {$foo.published_on}</em>
                    </div>
                    <div class="clearboth"></div>

                    <div class="rssItem">{insert name="highlight" html=$foo.body_html}</div>
                    <div class="clearboth"></div>

                    <!-- Read more -->
                    <p><a href="{$foo.url}">Read more &raquo;</a></p>

                    <!-- Naive Baysian Classification -->
                    <div class="categoryContainer">
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {$r->genericBayesInterface($foo.id, 'rss', 'feeds', $smarty.capture.document)}
                    </div>

                {/capture}

                {capture name=title_HL}{insert name="highlight" html=$foo.title}{/capture}
                {$r->widget($smarty.capture.title_HL, $smarty.capture.feed, $foo.url)}


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

{insert name="bayesFilterScript"}

{include file=$r->xhtml_footer}