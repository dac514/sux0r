{capture name=header}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer">
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">

                <h1>sux0r - it sux0rs up all the web</h1>
                {insert name="userInfo"}
                {$r->navlist()}

			</div>
            <div class="clearboth"></div>
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">
			<div id="leftside">

            <p><span class="hl">sux0r 2.0</span> is a blogging package, an RSS aggregator, a bookmark repository,
            and a photo publishing platform with a focus on Naive Bayesian categorization and probabilistic content.</p>

            <p>
            <a href="http://en.wikipedia.org/wiki/Naive_Bayes_classifier">Naive Bayesian categorization</a>
            is the ouija board of mathematics. Known for being good at filtering junk mail, the Naive Bayesian
            algorithm can categorize anything so long as there are coherent reference
            texts to work from. For example, categorizing documents in relation to a vector
            of political manifestos, or religious holy books, makes for a neat trick.
            More subjective magic 8-ball categories could be  "good vs. bad", risk assessment,
            insurance claim fraud, the list goes on.
            </p>

            <p>
            <span class="hl">sux0r 2.0</span> allows users
            to maintain lists of Naive Bayesian categories. These lists
            can be shared with other users. This allows groups
            to share, train, and use <span class="hl">sux0r</span> together.
            </p>

            <p>
            <span class="hl">sux0r 2.0</span> is <a href="http://sourceforge.net/projects/sux0r/">open source</a> and is distributed under
            the <a href="http://www.fsf.org/licensing/licenses/agpl-3.0.html">GNU Affero General Public License</a>.
            </p>

            <p>
            <img src="{$r->makeUrl('/', null, true)}/media/sux0r/assets/sux0r_logo.gif" alt="sux0r logo" width="300" height="232" border="0" class="sux0rLogo" />
            </p>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {* Capture content *}

            {capture name='title' assign='title'}CODENAME: Vorpal CMS{/capture}
            {capture name='welcome' assign='welcome'}

            <p>
            2008-11-08: <a href="http://sourceforge.net/people/viewjob.php?group_id=131752&amp;job_id=31512">Translations still wanted</a>.
            Currently we have Chinese (zh), English (en), Dutch (nl) and German (de).
            </p>


            <p>
            2008-10-29: <a href="http://www.youtube.com/watch?v=ppATTkbTIhg">A short YouTube tutorial</a>
            on how to start classifying documents using Naive Baysian Categorization with Sux0r CMS.
            </p>


            <p>
            2008-10-20: You must be logged in to use Naive Bayesian
            categorization. Once logged in, click on your nickname in the upper right,
            then click <a href="{$r->makeUrl('/bayes')}">Edit Bayesian</a>. After you have created your
            categories, navigate to <a href="{$r->makeUrl('/blog')}">Blog</a>, <a href="{$r->makeUrl('/feeds')}">Feeds</a>,
            or <a href="{$r->makeUrl('/bookmarks')}">Bookmarks</a> and the AJAX interface for
            Naive Bayesian categorization will be revealed. There isn't enough text to justify
            categorization in the <a href="{$r->makeUrl('/photos')}">Photos</a> module. So for now,
            it's omitted.
            </p>


            {/capture}
            {capture name='img' assign='img'}{$r->makeUrl('/', null, true)}/media/sux0r/assets/nullwhore.png{/capture}
            {capture name='caption' assign='caption'}Nullwhore Lives{/capture}

            {* Render widget *}
            {$r->widget($title, $welcome, 'http://www.sux0r.org/user/profile/conner_bw', $img, $caption)}

            <p id="sfLinks">
            Found a bug? <a href="http://sourceforge.net/tracker2/?atid=722155&amp;group_id=131752">Report it</a>.
            Need help? <a href="http://sourceforge.net/forum/forum.php?forum_id=447216">Ask in the Support forum</a>.
            Got ideas to discuss? Post in the <a href="http://sourceforge.net/forum/forum.php?forum_id=447217">Developers forum</a> or submit
            <a href="http://sourceforge.net/tracker2/?group_id=131752&amp;atid=722157">patches</a>.
            All that and more at the <a href="http://sourceforge.net/projects/sux0r/">sux0r SF.net project page</a>.
            </p>

            <p>
            <a href="http://www.php.net/" class="noBg"><img src="{$r->makeUrl('/', null, true)}/media/sux0r/flair/php5_logo.gif" alt="PHP5" border="0" class="flair" /></a>
            <a href="http://www.fsf.org/licensing/licenses/agpl-3.0.html" class="noBg"> <img src="{$r->makeUrl('/', null, true)}/media/sux0r/flair/agplv3-88x31.png" alt="AGPL" border="0" class="flair" /></a>
            <a href="http://sourceforge.net" class="noBg"><img src="http://sflogo.sourceforge.net/sflogo.php?group_id=131752&amp;type=1" width="88" height="31" border="0" alt="SourceForge.net Logo" class="flair" /></a>
            </p>

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