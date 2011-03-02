{capture name=header}

    {* RSS Feed *}
    <link rel="alternate" type="application/rss+xml" title="{$r->sitename} | {$r->gtext.bookmarks}" href="{$r->makeUrl('/bookmarks/rss', null, true)}" />

    {if $r->isLoggedIn() && $r->bool.bayes}
        {$r->jQueryInit()}
        {$r->genericBayesInterfaceInit()}
    {else}
        {$r->jQueryInit(false)}
    {/if}

    <script type="text/javascript">
    // <![CDATA[

    {if $r->isLoggedIn()}
    // Toggle subscription to a bookmark
    function toggleSubscription(bookmark_id) {

        var url = '{$r->url}/modules/bookmarks/ajax.toggle.php';
        var pars = { id: bookmark_id };

        $.ajax({
            url: url,
            type: 'post',
            data: pars,
            success: function(data, textStatus, transport) {
              // Toggle images
                var myImage = $.trim(transport.responseText);
                var myClass = 'img.subscription' + bookmark_id;
                var res = $(myClass);
                for (i = 0; i < res.length; i++) {
                    res[i].src = '{$r->url}/media/{$r->partition}/assets/' + myImage;
                }
            },
            error: function(transport) {
                if ($.trim(transport.responseText).length) {
                    alert(transport.responseText);
                }
            }
        });

    }
    {/if}

    // ]]>
    </script>

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

            {if $r->bool.bayes}
            <!-- Category filters -->
            {insert name="bayesFilters" form_url=$r->text.form_url hidden=$sort}
            {/if}

            {* Bookmarks *}
            {if $r->arr.bookmarks}
            {foreach from=$r->arr.bookmarks item=foo}

                <div class="bookmarkItem">

                    <div style="float:left; margin-right:0.5em;">
                    {$r->isSubscribed($foo.id)}
                    </div>

                    <div style="float:left; margin-bottom: 1em;">
                        <a href="{$foo.url}">{insert name="highlight" html=$foo.title}</a><br />
                        <em>{$r->gtext.published_on} : {$foo.published_on}</em>
                    </div>
                    <div class="clearboth"></div>

                    {insert name="highlight" html=$foo.body_html}

                    {$r->tags($foo.id)}

                    <!-- Naive Baysian Classification -->
                    <div class="categoryContainer">
                        {capture name=document}{$foo.title} {$foo.body_plaintext}{/capture}
                        {if $r->bool.bayes}{$r->genericBayesInterface($foo.id, 'bookmarks', 'bookmarks', $smarty.capture.document)}{/if}
                    </div>

                    {if $r->isLoggedIn()}{insert name="bookmarksEdit" id=$foo.id}{/if}

                </div>


            {/foreach}
            {else}
                <div class="bookmarkItem">
                {$r->gtext.not_found}
                </div>
            {/if}

            {$r->text.pager}


            </div>
        </td>
        <td style="vertical-align:top;">
            <div id="rightside">

            {if $sidetitle}<div class="sideListTitle">{$sidetitle}</div>{/if}

            {if $r->arr.bookmarks}
            <ul>
                <li><a href="{$datesort_url}">{$r->gtext.sort_date}</a></li>
                <li><a href="{$alphasort_url}">{$r->gtext.sort_alpha}</a></li>
            </ul>
            {/if}

            <ul>
                {if $r->isLoggedIn()}
                <li><a href="{insert name="myBookmarksLink"}">{$r->gtext.my_bookmarks}</a></li>
                {/if}
                <li><a href="{$r->makeUrl('/bookmarks/tag/cloud')}">{$r->gtext.tag_cloud}</a></li>
                <li><em><a href="{$r->makeUrl('/bookmarks/suggest')}">{$r->gtext.suggest} &raquo;</a></em></li>
            </ul>

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

{if $r->bool.bayes}{insert name="bayesFilterScript"}{/if}

{include file=$r->xhtml_footer}