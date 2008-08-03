{include file=$r->xhtml_header}

{literal}
<script type="text/javascript">
// <![CDATA[
function maximumWidth(myId, maxW) {
    var pix = document.getElementById(myId).getElementsByTagName('img');
    for (i = 0; i < pix.length; i++) {
        w = pix[i].width;
        h = pix[i].height;
        if (w > maxW) {
            f = 1 - ((w - maxW) / w);
            pix[i].width = w * f;
            pix[i].height = h * f;
        }
    }
}

window.onload = function() {
    maximumWidth('suxPhoto', {/literal}{#maxPhotoWidth#}{literal});
}
// ]]>
</script>
{/literal}

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

            <p>
            {insert name="editLinks"}
            {insert name="editLinks2" album_id=$r->album.id}
            </p>

			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">


                <div class="widget">
                    <h2><a href="{$r->text.back_url}">{$r->album.title}</a></h2>


                    <div class="prevNext" style="width:{#maxPhotoWidth#}px;">
                        {if $r->text.prev_id}<a href="{$r->makeUrl('photos/view')}/{$r->text.prev_id}" class="previous">&laquo; Previous</a>{/if}
                        {if $r->text.next_id}<a href="{$r->makeUrl('photos/view')}/{$r->text.next_id}" class="next">Next &raquo;</a>{/if}
                    </div>

                    <p id="suxPhoto">
                    {if $r->pho.image}<img class="photo" src="{$r->url}/data/photos/{$r->pho.image}" alt="" >{/if}
                    </p>

                    {if $r->pho.description}
                    <div class="description" style="width:{#maxPhotoWidth#}px;">
                    {$r->pho.description}
                    </div>
                    {/if}

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