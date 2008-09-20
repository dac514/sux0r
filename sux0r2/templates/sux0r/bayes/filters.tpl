<div id="nbf">

    {* Top *}
    <form action="{$r->text.form_url}" method="get" accept-charset="utf-8"
    onsubmit="$('nbSearch2').value = $('nbSearch').value; return true;"
    id="nbfCategoriesForm" >
        {$r->text.categories} :
        {html_options name='filter' id='filter' options=$r->getUserCategories() selected=$filter}
        <input id="nbfTopButton" type="submit" value="{$r->text.top}" />
        <input type="hidden" id="nbSearch2" name="search" value="{$search}" />
    </form>

    {* Threshold *}
    <form action="{$r->text.form_url}" method="get" accept-charset="utf-8"
    onsubmit="$('filter2').value = $('filter').value; $('nbSearch3').value = $('nbSearch').value; return true;"
    id="nbfSliderForm" >

        {* Hidden *}
        <input type="hidden" id="nbfThreshold" name="threshold" value="{$threshold}" />
        <input type="hidden" id="filter2" name="filter" value="{$filter}" />
        <input type="hidden" id="nbSearch3" name="search" value="{$search}" />
        {foreach from=$r->text.hidden key=k item=v}
        <input type="hidden" name="{$k}" value="{$v}" />
        {/foreach}

        {* Slider *}
        <div id="nbfTrack"><div id="nbfHandle"></div></div>
        <div id="nbfPercentage">{$threshold*100|truncate:5:""}%</div>
        <input id="nbfThresholdButton" type="submit" value="{$r->text.threshold}" />

    </form>

    {if isset($threshold)}
        <div class="nbfFilteredBy" >{$r->text.filter2}</div>
    {elseif $filter}
        <div class="nbfFilteredBy" >{$r->text.filter1}</div>
    {/if}

    <div id="nbSearchBox">{$r->text.search} : <input type="text" id='nbSearch' name='search' value='{$search}' /></div>

    <div class='clearboth'></div>

</div>

