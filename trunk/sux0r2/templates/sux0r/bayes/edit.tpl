{include file=$r->xhtml_header}

<script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>
{literal}
<script type='text/javascript'>
// <![CDATA[
function rm(myForm, myWarning) {
    if(confirm(myWarning)) {
        var x = document.getElementsByName(myForm);
        x[0].submit();
    }
}

function getDoc(id) {

    if (id) {

        {/literal}
        var url = '{$r->url}/modules/bayes/getDoc.php';
        var pars = 'id=' + id;
        {literal}

        var myAjax = new Ajax.Updater('placeholder', url, {
                method: 'get',
                parameters: pars

        });

        $('placeholder').addClassName('active');
    }

}
// ]]>
</script>
{/literal}

<div id="proselytizer">

    {* Header *}
    <div id="header">
        {insert name="userInfo"}
    </div>
    <div class="clearboth"></div>


    {* Content *}
    <div id="middle">

        {if $r->text.scores}
            <p><table border="1">
            <thead><tr><th>Categories</th><th>Scores</th></tr></thead>
            {foreach from=$r->text.scores key=k item=v}
            <tr><td>{$k}</td><td>{$v}</td></tr>
            {/foreach}
            </table></p>
        {/if}


        {if $validate.default.is_error !== false}
        <p class="errorWarning">{$r->text.form_error} :</p>
        {/if}


        <fieldset>
        <legend>Vectors</legend>

        {* Add a vector  ---------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="addvec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="addvec" />

        <p>
        {strip}
            {capture name=error}
            {validate id="addvec1" form="addvec" message=$r->text.form_error_1}
            {validate id="addvec2" form="addvec" message=$r->text.form_error_5}
            {/capture}
        {/strip}

        <label for="vector" {if $smarty.capture.error}class="error"{/if} > New vector :</label>
        <input type="text" name="vector" value="{$vector}" />
        <input type="submit" class="button" value="Add" />
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

        <label for="vector_id" {if $smarty.capture.error}class="error"{/if} > Remove vector :</label>
        {html_options name='vector_id' options=$r->getVectors() selected=$vector_id}
        <input type="button" class="button" value="Delete" onclick="rm('remvec', 'Are you sure you want to delete this vector?');" />
        {$smarty.capture.error}
        </p>

        </form>

        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>Categories</legend>

        {* Add a category --------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="addcat" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="addcat" />

        <p>
        {strip}
            {capture name=error}
            {validate id="addcat1" form="addcat" message=$r->text.form_error_2}
            {validate id="addcat2" form="addcat" message=$r->text.form_error_1}
            {validate id="addcat3" form="addcat" message=$r->text.form_error_6}
            {/capture}
        {/strip}

        <label for="category" {if $smarty.capture.error}class="error"{/if} > New category :</label>
        <input type="text" name="category" value="{$category}" />
        {html_options name='vector_id' options=$r->getVectors() selected=$vector_id}
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

        <label for="category_id" {if $smarty.capture.error}class="error"{/if} > Remove category :</label>
        {html_options name='category_id' options=$r->getCategories() selected=$category_id}
        <input type="button" class="button" value="Delete" onclick="rm('remcat', 'Are you sure you want to delete this category?');" />
        {$smarty.capture.error}
        </p>

        </form>


        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>Documents</legend>


        {* Train a document -------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="adddoc" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="adddoc" />

        <p>
        {strip}
            {capture name=error}
            {validate id="adddoc1" form="adddoc" message=$r->text.form_error_7}
            {validate id="adddoc2" form="adddoc" message=$r->text.form_error_4}
            {/capture}
        {/strip}

        <label for="document" {if $smarty.capture.error}class="error"{/if} > Train document :</label>
        <textarea name="document" cols='50' rows='10'>{$document}</textarea><br />
        <label>&nbsp;</label>{html_options name='category_id' options=$r->getCategories() selected=$category_id}
        <input type="submit" class="button" value="Add" />
        {$smarty.capture.error}
        </p>

        </form>


        {* Untrain a document ----------------------------------------------- *}

        <form action="{$r->text.form_url}" name="remdoc" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="remdoc" />

        <p>
        {strip}
            {capture name=error}
            {validate id="remdoc1" form="remdoc" message=$r->text.form_error_7}
            {/capture}
        {/strip}

        <label for="document_id" {if $smarty.capture.error}class="error"{/if} > Untrain document :</label>
        <select name="document_id" onmouseup="getDoc(this.value);">
        {html_options options=$r->getDocuments() selected=$document_id}
        </select>
        <input type="button" class="button" value="Delete" onclick="rm('remdoc', 'Are you sure you want to delete this document?');"/>
        {$smarty.capture.error}
        </p>

        <div id="placeholder"></div>

        </form>


        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>Categorize</legend>

        {* Categorize document ---------------------------------------------- *}

        <form action="{$r->text.form_url}" name="catdoc" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="catdoc" />

        <p>
        {strip}
            {capture name=error}
            {validate id="catdoc1" form="catdoc" message=$r->text.form_error_7}
            {validate id="catdoc2" form="catdoc" message=$r->text.form_error_3}
            {/capture}
        {/strip}

        <label for="document" {if $smarty.capture.error}class="error"{/if} > Categorize document :</label>
        <textarea name="cat_document" cols='50' rows='10'>{$cat_document}</textarea><br />
        <label>&nbsp;</label>{html_options name='vector_id' options=$r->getVectors() selected=$vector_id}
        <input type="submit" class="button" value="Categorize" />
        {$smarty.capture.error}
        </p>

        </form>

        {* // --------------------------------------------------------------- *}


        </fieldset>


        <fieldset>
        <legend>Shared</legend>
        <p>Todo, shared</p>
        </fieldset>


    </div>

</div>

{include file=$r->xhtml_footer}