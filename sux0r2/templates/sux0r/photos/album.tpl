{include file=$r->xhtml_header}

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

            xxx

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">


                <div class="widget">
                    <h2>Title</h2>

                    <div style="margin-left: 10px;">

                    {if $r->pho}
                    {foreach from=$r->pho item=foo name=bar}

                       <a href="{$r->makeUrl('/photos/view')}/{$foo.id}"><img class="thumbnail" src="{$r->url}/data/photos/{$foo.image}" alt="" ></a>

                    {/foreach}
                    {/if}

                    </div>

                    <div class="clearboth"></div>
                    <p>{$r->text.pager}</p>


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