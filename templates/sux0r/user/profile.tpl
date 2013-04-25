{capture name=header}

    {* OpenID *}
    <link rel="openid.server" href="{$r->makeUrl('/openid', null, true)}" />

    {if $r->isLoggedIn()}
        {$r->jQueryInit(false)}
        <script src="{$r->url}/includes/symbionts/jqueryAddons/jeditable/jquery.jeditable.mini.js" type="text/javascript"></script>
    {/if}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer" >
    <tr>
        <td colspan="3" style="vertical-align:top;">
            <div id="header">

                <h1>{$r->gtext.profile_of} : {$r->arr.profile.nickname}</h1>
                {insert name="userInfo"}
                {insert name="navlist"}

            </div>
            <div class="clearboth"></div>
        </td>
    </tr>
    <tr>
        <td style="vertical-align:top;">
            <div id="leftside">

            <p>{$r->gtext.profile_intro}</p>

            <!-- menu -->
            <div class='menucontainer'>
            <ul class='menulist'>
            <li><a href='{$r->makeUrl('/blog/author')}/{$r->arr.profile.nickname}'>{$r->gtext.blog}</a></li>
            <li><a href='{$r->makeUrl('/feeds/user')}/{$r->arr.profile.nickname}'>{$r->gtext.feeds}</a></li>
            <li><a href='{$r->makeUrl('/bookmarks/user')}/{$r->arr.profile.nickname}'>{$r->gtext.bookmarks}</a></li>
            <li><a href='{$r->makeUrl('/photos/user')}/{$r->arr.profile.nickname}'>{$r->gtext.photoalbums}</a></li>
            <li><div style="padding-bottom: 1em"></div></li>
            {insert name="editMenu" nickname=$r->arr.profile.nickname}
            </ul>
            </div>
            <div class='clearboth'></div>


            </div>
        </td>
        <td style="vertical-align:top;">
            <div id="middle2">

            {* Image *}
            {capture name=image}{strip}

                {if $r->arr.profile.image}
                {$r->myHttpServer()}{$r->url}/data/user/{$r->arr.profile.image|escape:'url'}
                {/if}

            {/strip}{/capture}

            {* Profile info, hcard microformat compatible - http://microformats.org/wiki/hcard *}
            {capture name=profile}

                <p class="vcard">
                {if $smarty.capture.image}<img class="photo" style="display:none;" src="{$smarty.capture.image}" alt="" />{/if}{* Hidden photo for hcard *}

                {$r->gtext.nickname} : {if $r->arr.profile.given_name || $r->arr.profile.family_name}<span class="nickname">{else}<span class="fn nickname">{/if}{$r->arr.profile.nickname}</span><br />
                {$r->gtext.email} : {mailto address=$r->arr.profile.email  encode='javascript_charcode'}<br />

                {if $r->arr.profile.given_name || $r->arr.profile.family_name}
                {$r->gtext.name} : <span class="fn">{$r->arr.profile.given_name} {$r->arr.profile.family_name}</span><br />
                {/if}

                <span class="adr">
                    {if $r->arr.profile.street_address}
                    {$r->gtext.street_address} : <span class="street-address">{$r->arr.profile.street_address}</span><br />
                    {/if}

                    {if $r->arr.profile.locality}
                    {$r->gtext.locality} : <span class="locality">{$r->arr.profile.locality}</span><br />
                    {/if}

                    {if $r->arr.profile.region}
                    {$r->gtext.region} : <span class="region">{$r->arr.profile.region}</span><br />
                    {/if}

                    {if $r->arr.profile.postcode}
                    {$r->gtext.postcode} : <span class="postal-code">{$r->arr.profile.postcode}</span> <br />
                    {/if}

                    {if $r->arr.profile.country}
                    {$r->gtext.country} : <span class="country-name">{$r->getCountry($r->arr.profile.country)}</span><br />
                    {/if}
                </span>

                {if $r->arr.profile.tel}
                {$r->gtext.tel} : <span class="tel">{$r->arr.profile.tel}</span><br />
                {/if}

                {if $r->arr.profile.url}
                {$r->gtext.url} : <a href="{$r->arr.profile.url}" class="url">{$r->arr.profile.url}</a> <br />
                {/if}

                {if $r->arr.profile.dob}
                {$r->gtext.dob} : <span class="bday">{$r->arr.profile.dob}</span><br />
                {/if}

                {if $r->arr.profile.gender}
                {$r->gtext.gender} : {$r->getGender($r->arr.profile.gender)} <br />
                {/if}

                {if $r->arr.profile.language}
                {$r->gtext.language} : {$r->getLanguage($r->arr.profile.language)} <br />
                {/if}

                {if $r->arr.profile.timezone}
                {$r->gtext.timezone} : {$r->arr.profile.timezone} <br />
                {/if}
                </p>

                {* OpenIDs *}
                <p>
                <img src="{$r->url}/media/{$r->partition}/assets/openid_icon.gif" alt="OpenID" class="openidIcon" /> <a href="{$r->makeUrl('/user/profile', null, true)}/{$r->arr.profile.nickname}">{$r->makeUrl('/user/profile', null, true)}/{$r->arr.profile.nickname}</a><br />
                {foreach from= $r->getOpenIDs($r->arr.profile.users_id) item=foo}
                <img src="{$r->url}/media/{$r->partition}/assets/openid_icon.gif" alt="OpenID" class="openidIcon" /> <a href="{$foo.openid_url}">{$foo.openid_url}</a><br />
                {/foreach}
                </p>

            {/capture}


            {$r->widget($r->gtext.profile, $smarty.capture.profile, null, $smarty.capture.image)}


            {* Acquaintances *}
            {if $r->acquaintances($r->arr.profile.users_id)}
            {capture name=acquaintances}{strip}
                {$r->acquaintances($r->arr.profile.users_id)}
            {/strip}{/capture}
            {$r->widget($r->gtext.acquaintances, $smarty.capture.acquaintances)}
            {/if}


            {* Stalkers *}
            {if $r->stalkers($r->arr.profile.users_id)}
            {capture name=stalkers}{strip}
                {$r->stalkers($r->arr.profile.users_id)}
            {/strip}{/capture}
            {$r->widget($r->gtext.stalkers, $smarty.capture.stalkers)}
            {/if}


            </div>
        </td>
        <td style="vertical-align:top;">
            <div id="rightside">
            <h2><a href="{$r->makeURL('/user/profile', null, true)}/{$r->arr.profile.nickname}/rss" class="noBg"><img class="rssIcon" src="{$r->url}/media/{$r->partition}/assets/rss_icon.png" alt="RSS Feed" /></a> {$r->gtext.minifeed}</h2>

            {insert name="lament" users_id=$r->arr.profile.users_id}

            <ul class="miniFeed">
            {foreach from=$r->arr.minifeed item=foo}
            <li><em><strong>{$foo.ts}</strong></em> <br />{$foo.body_html}</li>
            {foreachelse}
            <li>{$r->gtext.nothing}</li>
            {/foreach}
            </ul>

            </div>
        </td>
    </tr>
    <tr>
        <td colspan="3" style="vertical-align:bottom;">
            <div id="footer">
            {$r->copyright()}
            </div>
        </td>
    </tr>
</table>

{include file=$r->xhtml_footer}