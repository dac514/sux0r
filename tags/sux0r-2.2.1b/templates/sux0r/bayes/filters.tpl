<div id="nbf">

    {* Top *}
    <form action="{$r->text.form_url}" method="get" accept-charset="utf-8"
    onsubmit="$('#nbSearch2').val($('#nbSearch').val()); return true;"
    id="nbfCategoriesForm" >
        {$r->gtext.categories} :
        {html_options name='filter' id='filter' options=$r->getUserCategories() selected=$filter}
        <input id="nbfTopButton" type="submit" value="{$r->gtext.top}" />
        <input type="hidden" id="nbSearch2" name="search" value="{$search}" />
        {if $r->text.c}<input type="hidden" name="c" value="{$r->text.c}" />{/if}
    </form>

    {* Threshold *}
    <form action="{$r->text.form_url}" method="get" accept-charset="utf-8"
    onsubmit="$('#filter2').val($('#filter').val()); $('#nbSearch3').val($('#nbSearch').val()); return true;"
    id="nbfSliderForm" >

        {* Hidden *}
        <input type="hidden" id="nbfThreshold" name="threshold" value="{$threshold}" />
        <input type="hidden" id="filter2" name="filter" value="{$filter}" />
        <input type="hidden" id="nbSearch3" name="search" value="{$search}" />
        {foreach from=$r->arr.hidden key=k item=v}
        <input type="hidden" name="{$k}" value="{$v}" />
        {/foreach}
        {if $r->text.c}<input type="hidden" name="c" value="{$r->text.c}" />{/if}

        {* Slider *}
        <div id="nbfSlider"></div>
        <div id="nbfPercentage">{$threshold*100|truncate:5:""}%</div>
        <input id="nbfThresholdButton" type="submit" value="{$r->gtext.threshold}" />

    </form>

    <div id="nbSearchBox">{$r->gtext.search} : <input type="text" id='nbSearch' name='search' value='{$search}' /></div>
    <script type="text/javascript">
    // <![CDATA[
    $('#nbSearchBox').keypress(function(e){
        if(e.which == 13){
            e.preventDefault();
            $('#nbfSliderForm').submit();
            return false;
        }
    });
    // ]]>
    </script>

    <div class='clearboth'></div>

    {if isset($threshold)}
        <div class="nbfFilteredBy" >{$r->gtext.filter2}{if $search}, {$r->gtext.search|lower}: {$search}{/if}</div>
    {elseif $filter}
        <div class="nbfFilteredBy" >{$r->gtext.filter1}{if $search}, {$r->gtext.search|lower}: {$search}{/if}</div>
    {/if}

</div>
