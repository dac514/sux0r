{capture name=header}

    {literal}
    <style type="text/css">

    #navcontainerTest {
        width: 100%;
        background-color: #ff0000;
    }

    #navcontainerTest ul {
        margin: 0;
        padding: 0;
        z-index: 30;
        letter-spacing: 2px;
        font-weight: bold;
    }

    #navcontainerTest ul li {
        margin: 0;
        padding: 0;
        list-style: none;
        float: left;
    }

    #navcontainerTest ul li a {
        display: block;
        margin: 0 1px 0 0;
        padding: 4px 10px;
        background: #ff0000;
        color: #fff;
        text-align: center;
        text-decoration: none;
        border-right: 1px solid #ffffff;
    }

    #navcontainerTest ul li a:hover {
        background: #000000;
    }

    #navcontainerTest ul div {
        position: absolute;
        visibility: hidden;
        margin: 0;
        padding: 0;
        background: #eeeeee;
        border: 1px solid #ff0000;
    }

    #navcontainerTest ul div a {
        position: relative;
        display: block;
        margin: 0;
        padding: 5px 10px;
        width: auto;
        white-space: nowrap;
        text-align: left;
        text-decoration: none;
        background: #eeeeee;
        color: #000000;
        letter-spacing: normal;
        font-weight: normal;

    }

    #navcontainerTest ul div a:hover {
        background: #000000;
        color: #fff;
    }

    </style>



    <script type='text/javascript'>

    // Forked from / inspired by
    // http://javascript-array.com/scripts/simple_drop_down_menu

    var timeout	= 500;
    var closetimer	= 0;
    var ddmenuitem	= 0;

    // open hidden layer
    function mopen(id) {
        // cancel close timer
        mcancelclosetime();

        // close old layer
        if (ddmenuitem) ddmenuitem.style.visibility = 'hidden';

        // get new layer and show it
        ddmenuitem = document.getElementById(id);
        ddmenuitem.style.visibility = 'visible';

    }
    // close showed layer
    function mclose() {
        if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
    }

    // go close timer
    function mclosetime() {
        closetimer = window.setTimeout(mclose, timeout);
    }

    // cancel close timer
    function mcancelclosetime() {
        if (closetimer) {
            window.clearTimeout(closetimer);
            closetimer = null;
        }
    }

    // close layer when click-out
    document.onclick = mclose;

    // ]]>
    </script>
    {/literal}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer">
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">



<div id='navcontainerTest'>
<ul>

    <li><a href="{$r->url}/home">Home</a></li>

    <li><a href="{$r->url}/blog"
        onmouseover="mopen('m1')"
        onmouseout="mclosetime()">Blog</a>
        <div id="m1"
            onmouseover="mcancelclosetime()"
            onmouseout="mclosetime()">
        <a href="#">Administration</a>
        <hr />
        <a href="#">Publish</a>
        </div>
    </li>

    <li><a href="{$r->url}/feeds">Feeds</a></li>

    <li><a href="{$r->url}/bookmarks"
        onmouseover="mopen('m2')"
        onmouseout="mclosetime()">Bookmarks</a>
        <div id="m2"
            onmouseover="mcancelclosetime()"
            onmouseout="mclosetime()">
        <a href="#">Un</a>
        <a href="#">Deux Deux Deux</a>
        <a href="#">Trois</a>
        <a href="#">Quatre</a>
        </div>
    </li>

    <li><a href="{$r->url}/photos">Photos</a></li>
    <li><a href="{$r->url}/photos">Sourcecode</a></li>

</ul>
<div class="clearboth"></div>
</div>

                <!-- {*
                <h1>sux0r - it sux0rs up all the web</h1>
                {insert name="userInfo"}
                {$r->navlist()}
                *} -->

			</div>
            <div class="clearboth"></div>
		</td>
	</tr>
	<tr>
		<td style="vertical-align:top;">
			<div id="leftside">

            <p>
            <span class="hl">sux0r 2.0</span> is a blogging package, an RSS aggregator, a bookmark repository,
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
            the <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html">GNU General Public License</a>.
            </p>

            <p>
            <img src="{$r->url}/media/sux0r/assets/sux0r_logo.gif" alt="sux0r logo" width="300" height="232" border="0" class="sux0rLogo" />
            </p>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {* Capture content *}

            {capture name='title' assign='title'}CODENAME: Vorpal CMS{/capture}
            {capture name='welcome' assign='welcome'}

            <p>
            {$smarty.now|date_format:'%Y-%m-%d'}: <a href="http://sourceforge.net/people/viewjob.php?group_id=131752&amp;job_id=31512">Translations still wanted</a>.
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
            {capture name='img' assign='img'}{$r->myHttpServer()}{$r->url}/media/sux0r/assets/nullwhore.png{/capture}
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
            <a href="http://www.php.net/" class="noBg"><img src="{$r->url}/media/sux0r/assets/php5_logo.gif" alt="PHP5" border="0" class="flair" /></a>
            <a href="http://www.fsf.org/licensing/licenses/gpl-3.0.html" class="noBg"> <img src="{$r->url}/media/sux0r/assets/gplv3-88x31.png" alt="GPL" border="0" class="flair" /></a>
            <a href="http://sourceforge.net/projects/sux0r" class="noBg"><img src="http://sflogo.sourceforge.net/sflogo.php?group_id=131752&type=11" width="120" height="30" border="0" alt="Get sux0r at SourceForge.net. Fast, secure and Free Open Source software downloads" /></a>
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