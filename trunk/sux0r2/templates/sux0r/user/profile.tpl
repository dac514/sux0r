{capture name=header}
<link rel="openid.server" href="{$r->makeUrl('/openid', null, true)}" />
{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer" >
	<tr>
		<td colspan="3" style="vertical-align:top;">
			<div id="header">

                <h1>Profile of: {$r->profile.nickname}</h1>
                {insert name="userInfo"}
                {$r->navlist()}

			</div>
            <div class="clearboth"></div>
		</td>
	</tr>
	<tr>
        <td style="vertical-align:top;">
			<div id="leftside">

            <p>Lorem ipsum dolor sit amet, consec tetuer Lorem ipsum dolor sit amet</p>


            <!-- menu -->
            <div class='menucontainer'>
            <ul class='menulist'>
            <li><a href='{$r->makeUrl('/user/profile')}/{$r->profile.nickname}' class='selected'>{$r->profile.nickname}</a></li>
            {insert name="editMenu" nickname=$r->profile.nickname}
            </ul>
            </div>
            <div class='clearboth'></div>


			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="middle2">

            {capture name=profile}

                <p>
                {$r->text.nickname} : {$r->profile.nickname} <br />
                {$r->text.email} : {mailto address=$r->profile.email  encode='javascript'} <br />

                {if $r->profile.given_name || $r->profile.family_name}
                {$r->text.name} : {$r->profile.given_name} {$r->profile.family_name}<br />
                {/if}

                {if $r->profile.street_address}
                {$r->text.street_address} : {$r->profile.street_address} <br />
                {/if}

                {if $r->profile.locality}
                {$r->text.locality} : {$r->profile.locality} <br />
                {/if}

                {if $r->profile.region}
                {$r->text.region} : {$r->profile.region} <br />
                {/if}

                {if $r->profile.postcode}
                {$r->text.postcode} : {$r->profile.postcode} <br />
                {/if}

                {if $r->profile.country}
                {$r->text.country} : {$r->getCountry($r->profile.country)}<br />
                {/if}

                {if $r->profile.tel}
                {$r->text.tel} : {$r->profile.tel} <br />
                {/if}

                {if $r->profile.url}
                {$r->text.url} : {$r->profile.url} <br />
                {/if}

                {if $r->profile.dob}
                {$r->text.dob} : {$r->profile.dob} <br />
                {/if}

                {if $r->profile.gender}
                {$r->text.gender} : {$r->getGender($r->profile.gender)} <br />
                {/if}

                {if $r->profile.language}
                {$r->text.language} : {$r->getLanguage($r->profile.language)} <br />
                {/if}

                {if $r->profile.timezone}
                {$r->text.timezone} : {$r->profile.timezone} <br />
                {/if}


                {*
                {$r->profile.users_id}
                {$r->profile.accesslevel}
                {$r->profile.pavatar}
                {$r->profile.microid}
                *}

                </p>
            {/capture}

            {$r->widget('Profile', $smarty.capture.profile, null, 'http://localhost:8888/sux0r2/media/sux0r/pavatars/bunker.jpg')}
            {$r->widget('Friends', 'Todo')}

            </div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            <h2>Calendar</h2>
            <p>{$r->text.calendar}</p>


            <h2><a href="#todo" class="noBg"><img class="rssIcon" src="{$r->url}/media/{$r->partition}/assets/rss_icon.png" alt="RSS Feed" /></a> Minifeed</h2>
            <p>Todo</p>


			</div>
		</td>
	</tr>
	<tr>
		<td colspan="3" style="vertical-align:bottom;">
			<div id="footer">
			Footer
			</div>
		</td>
	</tr>
</table>

{include file=$r->xhtml_footer}