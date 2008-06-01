{include file=$r->xhtml_header}

<div id="proselytizer">

    {* Header *}
    <div id="header">
        {insert name="userInfo"}
    </div>
    <div class="clearboth"></div>


    {* Content *}
    <div id="middle">

        <fieldset>
        <legend>Bayesian Categories</legend>

        {* Add a vector *}

        <form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />

        {if $validate.default.is_error !== false}
        <p class="errorWarning">{$r->text.form_error} :</p>
        {/if}

        <p>
        {strip}
            {capture name=error}
            {* validate id="vector" message=$r->text.form_error_1 *}
            {/capture}
        {/strip}

        <label for="vector" {if $smarty.capture.error}class="error"{/if} > New vector :</label>
        <input type="text" name="vector" value="{$vector}" />
        {$smarty.capture.error}
        <input type="submit" class="button" value="Add" />
        </p>

        </form>


        {* Add a category *}

        <form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />

        {if $validate.default.is_error !== false}
        <p class="errorWarning">{$r->text.form_error} :</p>
        {/if}

        <p>
        {strip}
            {capture name=error}
            {* validate id="vector" message=$r->text.form_error_1 *}
            {/capture}
        {/strip}

        <label for="vector" {if $smarty.capture.error}class="error"{/if} > New category :</label>
        <input type="text" name="category" value="{$vector}" />
        {html_options name='vector' options=$r->todo selected=$language}
        {$smarty.capture.error}
        <input type="submit" class="button" value="Add" />
        </p>

        </form>


        </fieldset>

    </div>

</div>

{include file=$r->xhtml_footer}