{include file=$r->xhtml_header}

<div id="proselytizer">



{if $r->bool.edit}

    {* Edit Mode *}

    <p><strong>{$r->text.success}</strong>
    <p>{$r->text.success_edit}</p>
    <p style="text-align:center;">[ <a href="{$r->text.back_url}">{$r->text.back}</a> ]</p>

{else}

    {* Register Mode *}

    <h1>{$r->text.thanks}</h1>
    <p>{$r->text.success_register}</p>
    <p style="text-align:center;">[ <a href="{$r->makeUrl('/home')}">{$r->text.homepage}</a> ]</p>

{/if}


</div>

{include file=$r->xhtml_footer}