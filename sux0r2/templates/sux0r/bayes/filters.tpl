{if $r->getUserCategories()}

    <div id="nbf">

        {* Top *}
        <form action="{$r->text.form_url}" method="get" accept-charset="utf-8" >
            <div id="nbfCategories" >
                {$r->text.categories} :
                {html_options name='filter' id='filter' options=$r->getUserCategories() selected=$filter}
            </div>
            <input id="nbfTopButton" type="submit" value="{$r->text.top}" />
        </form>

        {* Threshold *}
        <form action="{$r->text.form_url}" method="get" accept-charset="utf-8" onsubmit="$('filter2').value = $('filter').value; return true;" >
           
            <input type="hidden" id="nbfThreshold" name="threshold" value="{$threshold}" />
            <input type="hidden" id="filter2" name="filter" value="{$filter}" />
            
            {* Slider *}
            <div id="nbfSlider">
                <div id="nbfTrack">
                    <div id="nbfHandle"></div>
                </div>
                <div id="nbfPercentage">&nbsp;</div>
            </div>
            <input id="nbfThresholdButton" type="submit" value="{$r->text.threshold}" />
        </form>

        {if isset($threshold)}
            <div class="nbfFilteredBy" >{$r->text.filter2}</div>
        {elseif $filter}
            <div class="nbfFilteredBy" >{$r->text.filter1}</div>
        {/if}

        <div class='clearboth'></div>

    </div>

    {literal}
    <script type="text/javascript" language="javascript">
    // <![CDATA[
    // Script has to come after slider otherwise it doesn't work

    // initial slider value
    sv = {/literal}{if $threshold}{$threshold}{else}0{/if}{literal};
    $('nbfThreshold').value = sv;
    $('nbfPercentage').innerHTML = (sv * 100).toFixed(2) + '%';

    // horizontal slider control
    new Control.Slider('nbfHandle', 'nbfTrack', {
            alignY: 5,
            sliderValue: sv,
            onSlide: function(v) {
                $('nbfPercentage').innerHTML = (v * 100).toFixed(2) + '%';
                $('nbfThreshold').value = v;
            }
    });

    // ]]>
    </script>
    {/literal}

{/if}
