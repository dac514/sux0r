{capture name=header}

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

    function getDoc(doc_id) {
        if (doc_id) {
            {/literal}
            var url = '{$r->url}/modules/bayes/getDoc.php';
            {literal}
            var pars = 'id=' + doc_id;

            var myAjax = new Ajax.Updater('placeholder1', url, {
                    method: 'get',
                    parameters: pars
            });
            $('placeholder1').addClassName('active');
            $('placeholder1').show();
        }
    }

    function getCat(document, vec_id) {

        {/literal}
        var url = '{$r->url}/modules/bayes/getCat.php';
        {literal}
        var pars = { document: document, id: vec_id }

        var myAjax = new Ajax.Updater('placeholder2', url, {
                method: 'post',
                parameters: pars
        });
        $('placeholder2').addClassName('active');
        $('placeholder2').show();

    }

    // ]]>
    </script>
    {/literal}

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer" >
	<tr>
		<td colspan="2" style="vertical-align:top;">
			<div id="header">
            {insert name="userInfo"}
			</div>
		</td>
	</tr>
	<tr>
        <td style="vertical-align:top;">
			<div id="leftside">

    {* Content *}
    <div id="middle">

        <noscript><p class="errorWarning">JavaScript must be enabled to categorize and delete!</p></noscript>

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
        {html_options name='vector_id' options=$r->getUserOwnedVectors() selected=$vector_id}
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
        {html_options name='vector_id' options=$r->getUserOwnedVectors() selected=$vector_id}
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
        {html_options name='category_id' options=$r->getUserOwnedCategories() selected=$category_id}
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
        <label>&nbsp;</label>{html_options name='category_id' options=$r->getUserTrainableCategories() selected=$category_id}
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
        {html_options options=$r->getUserOwnedDocuments() selected=$document_id}
        </select>
        <input type="button" class="button" value="Delete" onclick="rm('remdoc', 'Are you sure you want to delete this document?');"/>
        {$smarty.capture.error}
        </p>

        <div id="placeholder1"></div>

        </form>


        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>Categorize</legend>

        {* Categorize document ---------------------------------------------- *}

        <form action="{$r->text.form_url}" name="catdoc" method="post" accept-charset="utf-8">
        {* This is Ajax, no token or action is needed *}

        <p>
        <label for="document" >Categorize document :</label>
        <textarea name="cat_document" cols='50' rows='10'>{$cat_document}</textarea><br />
        <label>&nbsp;</label>{html_options name='vector_id' options=$r->getUserSharedVectors() selected=$vector_id}
        <input type="button" class="button" value="Categorize" onclick="getCat(this.form.cat_document.value, this.form.vector_id.value);" />
        {$smarty.capture.error}
        </p>

        <div id="placeholder2"></div>



        </form>

        {* // --------------------------------------------------------------- *}


        </fieldset>


        <fieldset>
        <legend>Share</legend>

        {* Share vector ----------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="sharevec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="sharevec" />

        <p>

        {validate id="sharevec1" form="sharevec" assign="sharevec_error1" message=$r->text.form_error_3}
        {validate id="sharevec2" form="sharevec" assign="sharevec_error2" message=$r->text.form_error_8}
        {validate id="sharevec3" form="sharevec" assign="sharevec_error3" message=$r->text.form_error_9}
        {validate id="sharevec4" form="sharevec" assign="sharevec_error4" message=$r->text.form_error_9}
        {validate id="sharevec5" form="sharevec" assign="sharevec_error5" message=$r->text.form_error_10}
        {validate id="sharevec6" form="sharevec" assign="sharevec_error6" message=$r->text.form_error_11}

        <label for="vector_id" {if $sharevec_error1}class="error"{/if} >Share vector:</label>
            {html_options name='vector_id' options=$r->getUserOwnedVectors() selected=$vector_id}
            {$sharevec_error1}
        </p>

        <p>
        <label for="users_id" {if $sharevec_error2 || $sharevec_error5 || $sharevec_error6}class="error"{/if} >With friend:</label>
            {* TODO: Get users from socialnetwork *}
            <select name="users_id">
            <option value="1">test</option>
            <option value="4">conner_bw</option>
            <option value="999">fake user</option>
            </select>
            {$sharevec_error2}
            {$sharevec_error5}
            {$sharevec_error6}
        </p>

        <p>
        <label for="trainer">&nbsp;</label>
            <input type="checkbox" name="trainer" value="1" /> <span {if $sharevec_error3}class="error"{/if}>Allow user to train documents?</span>
            {$sharevec_error3}
        </p>
        <p>
        <label for="trainer">&nbsp;</label>
            <input type="checkbox" name="owner" value="1" /> <span {if $sharevec_error4}class="error"{/if}>Owner? (If selected, the user can train documents)</span>
            {$sharevec_error4}
        </p>

        <p>
        <label>&nbsp;</label>
            <input type="submit" class="button" value="Share" />
        </p>

        </form>


        {* // --------------------------------------------------------------- *}


        </fieldset>

        <fieldset>
        <legend>Unshare</legend>

        {* Unhare vector ---------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="unsharevec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="unsharevec" />

        {validate id="unsharevec1" form="unsharevec" assign="unsharevec_error1" message=$r->text.form_error_12}


        {if $unsharevec_error1}<p class="error">{$unsharevec_error1}</p>{/if}

        {$r->getShareTable()}
        <center>
        <input type="button" class="button" value="Unshare" onclick="rm('unsharevec', 'Are you sure you want to unshare these vectors?');"/>
        </center>


        {* // --------------------------------------------------------------- *}

        </fieldset>


			</div>
		</td>
		<td style="vertical-align:top;">
			<div id="rightside">

            {capture name=stats}{$r->getCategoryStats()}{/capture}
            {if $smarty.capture.stats}
            <p>
            Stats:<br />
            {$smarty.capture.stats}
            </p>
            {/if}

            <p>Synopsis:</p>

            <p>A vector is a list of categories. You must have at least two categories
            in a vector to do <a href="http://en.wikipedia.org/wiki/Naive_Bayes_classifier">Bayesian classification</a>.</p>

            <p>For example, a vector named <strong>Feelings</strong>
            could have the categories <strong>Happy</strong>, <strong>Sad</strong> and <strong>Angry</strong>.
            A vector named <strong>Filter</strong> could have the categories <strong>Spam</strong>
            and <strong>Not-Spam</strong>.</p>

            <p>In contrast, you wouldn't put <strong>Spam</strong> in the
            <strong>Feelings</strong> vector because it doesn't belong to that list of categories.</p>

            <p>Please note that <strong>hundreds of documents</strong>
            need to be trained in each category before any ammount of accuracy is apparent.</p>



			</div>
		</td>
	</tr>
</table>


{include file=$r->xhtml_footer}