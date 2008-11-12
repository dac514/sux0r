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

                <h1>{$r->text.header|lower}</h1>
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


                <p>{$r->text.actions}</p>
                <ul>
                    {if $r->isLoggedIn()}
                    {insert name="feedsApproveLi"}
                    <li><a href="{$r->makeUrl('/feeds/manage')}">{$r->text.manage}</a></li>
                    {/if}
                    <li><em><a href="{$r->makeUrl('/feeds/suggest')}">{$r->text.suggest} &raquo;</a></em></li>
                </ul>


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
                    <div style="float:left; margin-right:0.5em;">
                    {$r->isSubscribed($foo.rss_feeds_id)}
                    </div>
                    <div style="float:left; margin-bottom: 1em;">
                    {$r->text.feed} : {$r->feedLink($foo.rss_feeds_id)}<br />
                    <em>{$r->text.published_on} : {$foo.published_on}</em>
                    </div>
                    <div class="clearboth"></div>

                    <div class="rssItem">{insert name="highlight" html=$foo.body_html}</div>

                    <!-- Read more -->
                    <p class="readMore"><a href="{$foo.url}">{$r->text.read_more} &raquo;</a></p>

                    {capture name=nbc}{strip}
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {$r->genericBayesInterface($foo.id, 'rss', 'feeds', $smarty.capture.document)}
                    {/strip}{/capture}
                    {if $smarty.capture.nbc}
                        <!-- Naive Baysian Classification -->
                        <div class="categoryContainer">
                        {$smarty.capture.nbc}
                        </div>
                    {/if}


                {/capture}

                {capture name=title_HL}{insert name="highlight" html=$foo.title}{/capture}
                {$r->widget($smarty.capture.title_HL, $smarty.capture.feed, $foo.url)}


            {/foreach}
            {else}
                <p>{$r->text.not_found}</p>
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