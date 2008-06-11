{include file=$r->xhtml_header}

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

            <p>
            Current month<br />
            Link to article<br />
            Link to article<br />
            Link to article<br />
            Link to article<br />
            </p>

            <p>
            Most recent comments<br />
            Link to article#comment<br />
            Link to article#comment<br />
            Link to article#comment<br />
            Link to article#comment<br />
            </p>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            <p>
            Author:
            [ Dropdown ]
            Tags:
            [ Dropdown ]
            Month/Year:
            [ Dropdown ]
            [ Go! ]
            </p>

                <div class="widget">
                    <h2>Title</h2>
                    <div class="floatright">
                        <img src="{$r->url}/media/{$r->partition}/pavatars/bunker.jpg" alt="" width="80" height="80"><br>
                        Caption
                    </div>

                    <p>Date, Author, Tags</p>
                    <p>Lorem ipsum dolor sit amet, consectetuer...</p>

                    <div class="clearboth"></div>
                    <p>Permanent Link, Comments (0), [ Bayesian Categorize ]</p>

                    <div class="flair"><p>
                    <a href='http://slashdot.org/slashdot-it.pl?op=basic&url={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/slashdot.gif' alt='Slashdot' width='16' height='16' /></a>
                    <a href='http://digg.com/submit?url={$url|escape:'url, UTF-8'}&title={$title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/digg.gif' alt='Digg' width='16' height='16' /></a>
                    <a href='http://www.facebook.com/share.php?u={$url|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/facebook.gif' alt='Facebook' width='16' height='16' /></a>
                    <a href='http://www.myspace.com/index.cfm?fuseaction=postto&t={$title|escape:'url, UTF-8'}&c=&u={$url|escape:'url, UTF-8'}&l=' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/myspace.gif' alt='Myspace' width='16' height='16' /></a>
                    <a href='http://www.stumbleupon.com/submit?url={$url|escape:'url, UTF-8'}&title={$title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/stumbleupon.gif' alt='StumbleUpon' width='16' height='16' /></a>
                    <a href='http://del.icio.us/login/?url={$url|escape:'url, UTF-8'}&title={$title|escape:'url, UTF-8'}' target='_blank' ><img src='{$r->url}/media/{$r->partition}/flair/delicious.gif' alt='Del.icio.us' width='16' height='16' /></a>
                    </p></div>

                    <b class="bb"><b></b></b>
                </div>

                <div class="widget">
                    <h2>Lorem ipsum</h2>
                    <div class="floatright">
                    <img src="{$r->url}/media/{$r->partition}/pavatars/bunker.jpg" alt="" width="80" height="80"><br>
                    Caption
                    </div>

                    <p>Date, Author, Tags</p>
                    <p>Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer
                    Lorem ipsum dolor sit amet, consec tetuer Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer
                    Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consect etuer Lorem ipsum dolor sit amet, consectetuer
                    Lorem ipsum dolor sit amet, consec tetuer Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer
                    </p>
                    <div class="clearboth"></div>
                    <p>Permanent Link, Comments (0), [ Facebook, Digg, etc ], [ Bayesian Categorize ]</p>
                    <b class="bb"><b></b></b>
                </div>

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