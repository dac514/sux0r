 {capture name=header}

{* Latest version *}
{assign var='version' value='2.1.0'}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer">
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">

                <h1>Bayesian Feed Filter </h1>
                {insert name="userInfo"}
				{insert name="navlist"}

			</div>
            <div class="clearboth"></div>
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">
			<div id="leftside">
<h2>About the Bayesian Feed Filtering Project</h2>
<p>The Bayesian Feed Filtering (BayesFF) project will be trying to identify those articles that are of interest to specific researchers from a set of RSS feeds of Journal Tables of Content by applying the same approach that is used to filter out junk emails.</p>

<p>We will investigate the performance of a tool (Sux0r) that will aggregate and filter a range of RSS and ATOM feeds selected by a user. The algorithm used for the filtering is similar to that used to identify spam in many email filters only in this case it will be "trained" to identify items that are interesting and should be highlighted, not those that should be junked.</p>

<p>An important element of the project is investigating whether the filtering is effective enough to be helpful to users (specifically, in this case, researchers looking at journal tables of content for interesting newly-published papers) and disseminating information about the potential of this approach within the JISC community. We appreciate that the potential applicability of the technique is much wider, it applies to any area where a user might want to monitor alerts from a wide range of sources in the knowledge that many of the items in the feeds will be irrelevant. Anyone who has subscribed to dozens of seemingly relevant feeds only to find that they are presented with more items than they can scan is familiar with this problem.</p>
<h2>About sux0r </h2>
<p>
            <span class="highlight">sux0r {$version}</span> is a blogging package, an RSS aggregator, a bookmark repository,
            and a photo publishing platform with a focus on Naive Bayesian categorization and probabilistic content.
            <a href="http://openid.net/">OpenID</a> enabled <em>(version 1.1);</em> as both a consumer and a provider.
            </p>

            <p>
            <a href="http://en.wikipedia.org/wiki/Naive_Bayes_classifier">Naive Bayesian categorization</a>
            is the ouija board of mathematics. Known for being good at filtering junk mail, the Naive Bayesian
            algorithm can categorize anything so long as there are coherent reference
            texts to work from. For example, categorizing documents in relation to a vector
            of political manifestos, or religious holy books, makes for a neat trick.
            More subjective magic 8-ball categories could be  "good vs. bad", risk assessment,
            insurance claim fraud, whatever you want.
            </p>

            <p>
            <span class="highlight">sux0r</span> allows users
            to maintain lists of Naive Bayesian categories. These lists
            can be shared with other users. This allows groups
            to share, train, and use <span class="highlight">sux0r</span> together.
            </p>

            <p>
            <span class="highlight">sux0r {$version}</span> is <a href="http://sourceforge.net/projects/sux0r/">open source</a> and is distributed under
            the <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.
            </p>

<p>
            Found a bug? <a href="http://sourceforge.net/tracker2/?atid=722155&amp;group_id=131752">Report it</a>.
            Need help? <a href="http://sourceforge.net/forum/forum.php?forum_id=447216">Ask in the Support forum</a>.
            Got ideas to discuss? Post in the <a href="http://sourceforge.net/forum/forum.php?forum_id=447217">Developers forum</a> or submit
            <a href="http://sourceforge.net/tracker2/?group_id=131752&amp;atid=722157">patches</a>.
            All that and more at the <a href="http://sourceforge.net/projects/sux0r/">sux0r SF.net project page</a>.
            </p>

            <p style="text-align: center;">
            <a href="http://www.php.net/" class="noBg"><img src="{$r->url}/media/sux0r/assets/php5_logo.gif" alt="PHP5" border="0" class="flair" /></a>
            <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html" class="noBg"> <img src="{$r->url}/media/sux0r/assets/gplv3-88x31.png" alt="GPL" border="0" class="flair" /></a>
            <a href="http://sourceforge.net/projects/sux0r" class="noBg"><img src="http://sflogo.sourceforge.net/sflogo.php?group_id=131752&amp;type=11" width="120" height="30" border="0" alt="Get sux0r at SourceForge.net. Fast, secure and Free Open Source software downloads"  class="flair" /></a>
            </p>

<h2>Bayesian Feed Filtering News</h2>
<script src="http://feeds.feedburner.com/BayesFF?format=sigpro" type="text/javascript" ></script><noscript><p>Subscribe to RSS headline updates from: <a href="http://feeds.feedburner.com/BayesFF"></a><br/>Powered by FeedBurner</p> </noscript>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {* Capture content *}

            {capture name='title' assign='title'}How to Use Bayesian Feed Filter{/capture}
            {capture name='welcome' assign='welcome'}

<p>View the help videos below to get started (best viewed in fullscreen mode).</p>
<h3>Register an Account</h3>
<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='280'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=75098' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=75098' allowFullScreen='true' width='450' height='280' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
<br />
<h3>Login &amp; Subscribe to RSS Feeds</h3>
<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='280'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=75116' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=75116' allowFullScreen='true' width='450' height='280' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
<br />
<h3>Training RSS Items</h3>    
<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='280'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=75132' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=75132' allowFullScreen='true' width='450' height='280' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
<br />
<h3>Filtering by Threshold and Keywords</h3>
<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='280'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=77559' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=77559' allowFullScreen='true' width='450' height='280' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>
<br />
<h3>Training and Categorizing Using Other Documents</h3>
<object classid='clsid:d27cdb6e-ae6d-11cf-96b8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,115,0' width='450' height='280'><param name='movie' value='http://screenr.com/Content/assets/screenr_1116090935.swf' /><param name='flashvars' value='i=77483' /><param name='allowFullScreen' value='true' /><embed src='http://screenr.com/Content/assets/screenr_1116090935.swf' flashvars='i=77483' allowFullScreen='true' width='450' height='280' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed></object>

            {/capture}
            {* Render widget *}
            {$r->widget($title, $welcome, 'http://sourceforge.net/projects/sux0r/', $img, '', 'http://www.sux0r.org/user/profile/conner_bw')}

            



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


{include file=$r->xhtml_footer}