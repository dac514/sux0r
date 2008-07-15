{if $r->getUserCategories()}

    <div style="margin-top: 5px;">

        <form action="{$form_url}" method="get" accept-charset="utf-8" >
            <div style="float:left;">
                Categories :
                {html_options name='filter' id='filter' options=$r->getUserCategories() selected=$filter}
            </div>
            <div style="float:left; margin-left: 0.5em;"><input type="submit" value="Top" /></div>
        </form>

        <form action="{$form_url}" method="get" accept-charset="utf-8" onsubmit="$('filter2').value = $('filter').value; return true;" >

            <input type="hidden" id="threshold" name="threshold" value="0" />
            <input type="hidden" id="filter2" name="filter" value="0" />

            {* Slider *}
            <div style="float:left; margin-left: 1em; padding-top:0.5em; ">
                <div id="nbTrack" style="width:100px; background-color:#ccc; height:10px; float:left;">
                    <div id="nbHandle" style="width:10px; height:15px; background-color:#f00; cursor:crosshair;"></div>
                </div>
                <div style="float:left; margin-left: 0.5em;" id="nbPercentage">&nbsp;</div>
            </div>
            <div style="float:left; margin-left: 0.5em;"><input type="submit" value="Threshold" /></div>


        </form>

        {if isset($threshold)}
            <div style="float:left; margin-left: 0.5em; padding-top:0.5em; color: red; ">Filtering by threshold</div>
        {elseif $filter}
            <div style="float:left; margin-left: 0.5em; padding-top:0.5em; color: red; ">Filtering by top</div>
        {/if}

        <div class='clearboth'></div>

    </div>

    {literal}
    <script type="text/javascript" language="javascript">
    // <![CDATA[
    // Script has to come after slider xhtml otherwise it doesn't work

    // initial slider value
    sv = {/literal}{if $threshold}{$threshold}{else}0{/if}{literal};
    $('threshold').value = sv;
    $('nbPercentage').innerHTML = (sv * 100).toFixed(2) + '%';

    // horizontal slider control
    new Control.Slider('nbHandle', 'nbTrack', {
            alignY:5,
            sliderValue: sv,
            onSlide: function(v) {
                $('nbPercentage').innerHTML = (v * 100).toFixed(2) + '%';
                $('threshold').value = v;
            }
    });

    // ]]>
    </script>
    {/literal}

{/if}
