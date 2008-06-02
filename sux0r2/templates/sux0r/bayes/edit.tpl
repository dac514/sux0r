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
        <legend>Categories</legend>

        {if $validate.addvec.is_error !== false || $validate.addcat.is_error !== false }
        <p class="errorWarning">{$r->text.form_error} :</p>
        {/if}


        {* Add a vector  ---------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="addvec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="addvec" />

        <p>
        {strip}
            {capture name=error}
            {validate id="addvec1" form="addvec" message=$r->text.form_error_1}
            {/capture}
        {/strip}

        <label for="vector" {if $smarty.capture.error}class="error"{/if} > New vector :</label>
        <input type="text" name="vector" value="{$vector}" />
        <input type="submit" class="button" value="Add" />
        {$smarty.capture.error}
        </p>

        </form>


        {* Add a category --------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="addcat" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="addcat" />

        <p>
        {strip}
            {capture name=error}
            {validate id="addcat1" form="addcat" message=$r->text.form_error_2}
            {validate id="addcat2" form="addcat" message=$r->text.form_error_1}
            {/capture}
        {/strip}

        <label for="category" {if $smarty.capture.error}class="error"{/if} > New category :</label>
        <input type="text" name="category" value="{$category}" />
        {html_options name='vector' options=$r->getVectors() selected=$todo}
        <input type="submit" class="button" value="Add" />
        {$smarty.capture.error}
        </p>

        </form>


        {* Remove a category ------------------------------------------------ *}

        <form action="{$r->text.form_url}" name="remcat" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="remcat" />

        <p>
        {strip}
            {capture name=error}
            {validate id="remcat1" form="remcat" message=$r->text.form_error_4}
            {/capture}
        {/strip}

        <label for="category" {if $smarty.capture.error}class="error"{/if} > Remove category :</label>
        {html_options name='category' options=$r->getCategories() selected=$todo}
        <input type="submit" class="button" value="Delete" />
        {$smarty.capture.error}
        </p>

        </form>


        {* Remove a vector -------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="remvec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="remvec" />

        <p>
        {strip}
            {capture name=error}
            {validate id="remvec1" form="remvec" message=$r->text.form_error_3}
            {/capture}
        {/strip}

        <label for="vector" {if $smarty.capture.error}class="error"{/if} > Remove vector :</label>
        {html_options name='vector' options=$r->getVectors() selected=$todo}
        <input type="submit" class="button" value="Delete" />
        {$smarty.capture.error}
        </p>

        </form>

        </fieldset>

        <p />
        <fieldset>
        <legend>Documents</legend>
        <p>Todo, documents</p>
        </fieldset>

        <p />
        <fieldset>
        <legend>Shared</legend>
        <p>Todo, shared</p>
        </fieldset>


    </div>

</div>

{include file=$r->xhtml_footer}