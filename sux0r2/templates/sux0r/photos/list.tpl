{capture name=header}{literal}

<style type="text/css">
    #leftside { width: 468px; margin-left: 2px; }
    #rightside { width: 468px; }
</style>

{/literal}{/capture}{strip}
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

            {$r->widget('Title', '<p>Lorem ipsum dolor sit amet, consectetuer...</p>', 'www.trotch.com', 'http://localhost:8888/sux0r2/media/sux0r/pavatars/bunker.jpg', 'Caption my big ass caption', null, 'floatleft')}


                <div class="widget">
                    <h2>Title</h2>
                    <div class="floatleft">
                    <img src="{$r->url}/media/{$r->partition}/pavatars/bunker.jpg" alt="" width="80" height="80"><br>
                    </div>
                    <p>
                    Title<br />
                    Description<br />
                    2007-01-01 12:12:12<br />
                    ## Photos
                    </p>
                    <div class="clearboth"></div>
                    <b class="bb"><b></b></b>
                </div>


			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {$r->widget('Title', '<p>Lorem ipsum dolor sit amet, consectetuer...</p>', 'www.trotch.com', 'http://localhost:8888/sux0r2/media/sux0r/pavatars/bunker.jpg', 'Caption my big ass caption', null, 'floatleft')}
            {$r->widget('Title', '<p>Lorem ipsum dolor sit amet, consectetuer...</p>', 'www.trotch.com', 'http://localhost:8888/sux0r2/media/sux0r/pavatars/bunker.jpg', 'Caption my big ass caption', null, 'floatleft')}


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