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

            {$r->widget('Title', '<p>Lorem ipsum dolor sit amet, consectetuer...</p>', 'www.trotch.com', 'http://localhost:8888/sux0r2/media/sux0r/assets/nullwhore.png', 'Caption my big ass caption')}


                <div class="widget">
                    <h2>Lorem ipsum</h2>
                    <div class="floatleft">
                    <img src="{$r->url}/media/{$r->partition}/assets/nullwhore.png" alt="" width="80" height="80"><br>
                    Caption
                    </div>
                    <p>Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer
                    Lorem ipsum dolor sit amet, consec tetuer Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer
                    Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consect etuer Lorem ipsum dolor sit amet, consectetuer
                    Lorem ipsum dolor sit amet, consec tetuer Lorem ipsum dolor sit amet, consectetuer Lorem ipsum dolor sit amet, consectetuer
                    </p>
                    <div class="clearboth"></div>
                    <b class="bb"><b></b></b>
                </div>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

                <div class="widget">
                    <h2>Lorem ipsum dolor sit amet, Lorem ipsum dolor sit amet, consectetuer</h2>

                    <!-- menu -->
                    <div class='menucontainer'>
                    <ul class='menulist'>
                    <li><a href='#' class='selected'>Item One</a></li>
                    <li><a href='cake'>Item Two</a></li>
                    <li><a href='#'>Item Three</a></li>
                    <li><a href='http://www.trotch.com'>Item Four</a></li>
                    </ul>
                    </div>
                    <div class='clearboth'></div>

                    <div class="clearboth"></div>
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