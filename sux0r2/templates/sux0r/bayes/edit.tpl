{capture name=header}

    {$r->jQueryInit()}

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

            var url = '{$r->url}/modules/bayes/ajax.getDoc.php';
            var pars = { id: doc_id };

            $.ajax({
                url: url,
                type: 'post',
                data: pars,
                success: function(data, textStatus, transport) {
                    $('#placeholder1').html(transport.responseText);
                    $('#placeholder1').addClass('active');
                    $('#placeholder1').show();
                    $('#placeholder1').effect('highlight');
                },
            });

        }
        else {
            $('#placeholder1').hide('slow');
        }
    }

    function getCat(document, vec_id) {

        var url = '{$r->url}/modules/bayes/ajax.getCat.php';
        var pars = { document: document, id: vec_id };

        $.ajax({
            url: url,
            type: 'post',
            data: pars,
            success: function(data, textStatus, transport) {
                $('#placeholder2').html(transport.responseText);
                $('#placeholder2').addClass('active');
                $('#placeholder2').show();
                $('#placeholder2').effect('highlight');
            },
        });

    }

    // ]]>
    </script>

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<table id="proselytizer" >
    <tr>
        <td colspan="2" style="vertical-align:top;">
            <div id="header">
                {insert name="userInfo"}
                <div class='clearboth'></div>
            </div>
        </td>
    </tr>
    <tr>
        <td style="vertical-align:top;">
            <div id="leftside">

    {* Content *}
    <div id="middle">



        {* FIXME: Form is not always $form_name
        {if $validate.$form_name.is_error !== false}
        <p class="errorWarning">{$r->gtext.form_error} :</p>
        {/if}
        *}

        <fieldset>
        <legend>{$r->gtext.vectors}</legend>

        {* Add a vector  ---------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="addvec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="addvec" />

        <p>
        {strip}
            {capture name=error}
            {validate id="addvec1" form="addvec" message=$r->gtext.form_error_1}
            {validate id="addvec2" form="addvec" message=$r->gtext.form_error_5}
            {/capture}
        {/strip}

        <label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.new_vec} :</label>
        <input type="text" name="vector" value="{$vector}" />
        <input type="submit" class="button" value="{$r->gtext.add}" />
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
            {validate id="remvec1" form="remvec" message=$r->gtext.form_error_3}
            {/capture}
        {/strip}

        <label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.remove_vec} :</label>
        {html_options name='vector_id' options=$r->getUserOwnedVectors() selected=$vector_id}
        <input type="button" class="button" value="{$r->gtext.delete}" onclick="rm('remvec', '{$r->gtext.alert_vec}');" />
        {$smarty.capture.error}
        </p>

        </form>

        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>{$r->gtext.categories}</legend>

        {* Add a category --------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="addcat" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="addcat" />

        <p>
        {strip}
            {capture name=error}
            {validate id="addcat1" form="addcat" message=$r->gtext.form_error_2}
            {validate id="addcat2" form="addcat" message=$r->gtext.form_error_1}
            {validate id="addcat3" form="addcat" message=$r->gtext.form_error_6}
            {/capture}
        {/strip}

        <label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.new_cat} :</label>
        <input type="text" name="category" value="{$category}" />
        {html_options name='vector_id' options=$r->getUserOwnedVectors() selected=$vector_id}
        <input type="submit" class="button" value="{$r->gtext.add}" />
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
            {validate id="remcat1" form="remcat" message=$r->gtext.form_error_4}
            {/capture}
        {/strip}

        <label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.remove_cat} :</label>
        {html_options name='category_id' options=$r->getUserOwnedCategories() selected=$category_id}
        <input type="button" class="button" value="{$r->gtext.delete}" onclick="rm('remcat', '{$r->gtext.alert_cat}');" />
        {$smarty.capture.error}
        </p>

        </form>


        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>{$r->gtext.documents}</legend>


        {* Train a document -------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="adddoc" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="adddoc" />

        <p>
        {strip}
            {capture name=error}
            {validate id="adddoc1" form="adddoc" message=$r->gtext.form_error_7}
            {validate id="adddoc2" form="adddoc" message=$r->gtext.form_error_4}
            {/capture}
        {/strip}

        <label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.add_doc} :</label>
        <textarea name="document" cols='50' rows='10'>{$document}</textarea><br />
        <label>&nbsp;</label>{html_options name='category_id' options=$r->getUserTrainableCategories() selected=$category_id}
        <input type="submit" class="button" value="{$r->gtext.train}" />
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
            {validate id="remdoc1" form="remdoc" message=$r->gtext.form_error_7}
            {/capture}
        {/strip}

        <label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.remove_doc} :</label>
        <select name="document_id" onchange="getDoc(this.value);">
        <option value="">---</option>
        {html_options options=$r->getUserOwnedDocuments() selected=$document_id}
        </select>
        <input type="button" class="button" value="{$r->gtext.delete}" onclick="rm('remdoc', '{$r->gtext.alert_doc}');"/>
        {$smarty.capture.error}
        </p>

        <div id="placeholder1"></div>

        </form>


        {* // --------------------------------------------------------------- *}

        </fieldset>


        <fieldset>
        <legend>{$r->gtext.categorize}</legend>

        {* Categorize document ---------------------------------------------- *}

        <form action="{$r->text.form_url}" name="catdoc" method="post" accept-charset="utf-8">
        {* This is Ajax, no token or action is needed *}

        <p>
        <label >{$r->gtext.cat_doc} :</label>
        <textarea name="cat_document" cols='50' rows='10'>{$cat_document}</textarea><br />
        <label>&nbsp;</label>{html_options name='vector_id' options=$r->getUserSharedVectors() selected=$vector_id}
        <input type="button" class="button" value="{$r->gtext.categorize}" onclick="getCat(this.form.cat_document.value, this.form.vector_id.value);" />
        {$smarty.capture.error}
        </p>

        <div id="placeholder2"></div>

        </form>

        {* // --------------------------------------------------------------- *}


        </fieldset>


        <fieldset>
        <legend>{$r->gtext.share}</legend>

        {* Share vector ----------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="sharevec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="sharevec" />

        <p>

        {validate id="sharevec1" form="sharevec" assign="sharevec_error1" message=$r->gtext.form_error_3}
        {validate id="sharevec2" form="sharevec" assign="sharevec_error2" message=$r->gtext.form_error_8}
        {validate id="sharevec3" form="sharevec" assign="sharevec_error3" message=$r->gtext.form_error_9}
        {validate id="sharevec4" form="sharevec" assign="sharevec_error4" message=$r->gtext.form_error_9}
        {validate id="sharevec5" form="sharevec" assign="sharevec_error5" message=$r->gtext.form_error_10}
        {validate id="sharevec6" form="sharevec" assign="sharevec_error6" message=$r->gtext.form_error_11}

        <label {if $sharevec_error1}class="error"{/if} >{$r->gtext.share_vec} :</label>
            {html_options name='vector_id' options=$r->getUserOwnedVectors() selected=$vector_id}
            {$sharevec_error1}
        </p>

        <p>
        <label {if $sharevec_error2 || $sharevec_error5 || $sharevec_error6}class="error"{/if} >{$r->gtext.with} :</label>
            {html_options name='users_id' options=$r->getFriends() selected=$users_id}
            {$sharevec_error2}
            {$sharevec_error5}
            {$sharevec_error6}
        </p>

        <p>
        <label>&nbsp;</label>
            <input type="checkbox" name="trainer" value="1" /> <span {if $sharevec_error3}class="error"{/if}>{$r->gtext.trainer}</span>
            {$sharevec_error3}
        </p>
        <p>
        <label>&nbsp;</label>
            <input type="checkbox" name="owner" value="1" /> <span {if $sharevec_error4}class="error"{/if}>{$r->gtext.owner2}</span>
            {$sharevec_error4}
        </p>

        <p>
        <label>&nbsp;</label>
            <input type="submit" class="button" value="{$r->gtext.share}" />
        </p>

        </form>


        {* // --------------------------------------------------------------- *}


        </fieldset>

        <fieldset>
        <legend>{$r->gtext.unshare}</legend>

        {* Unhare vector ---------------------------------------------------- *}

        <form action="{$r->text.form_url}" name="unsharevec" method="post" accept-charset="utf-8">
        <input type="hidden" name="token" value="{$token}" />
        <input type="hidden" name="action" value="unsharevec" />

        {validate id="unsharevec1" form="unsharevec" assign="unsharevec_error1" message=$r->gtext.form_error_12}

        {if $unsharevec_error1}<p class="error">{$unsharevec_error1}</p>{/if}

        {$r->getShareTable()}
        <center><br />
        <input type="button" class="button" value="{$r->gtext.unshare}" onclick="rm('unsharevec', '{$r->gtext.alert_unshare}');"/>
        </center>

        </form>


        {* // --------------------------------------------------------------- *}

        </fieldset>

        <p><a href="{$r->makeUrl('/user/profile')}">{$r->gtext.back_2} &raquo;</a></p>

        </div></div>

        </td>
        <td style="vertical-align:top;">
            <div id="rightside">

            {capture name=stats}{$r->getCategoryStats()}{/capture}
            {if $smarty.capture.stats}
            <p>{$r->gtext.stats}:</p>
            {$smarty.capture.stats}<br />
            {/if}

            <p>{$r->gtext.synopsis}:</p>
            <p>{$r->gtext.synopsis_1}</p>
            <p>{$r->gtext.synopsis_2}</p>
            <p>{$r->gtext.synopsis_3}</p>
            <p>{$r->gtext.synopsis_4}</p>

            </div>
        </td>
    </tr>
</table>


{include file=$r->xhtml_footer}