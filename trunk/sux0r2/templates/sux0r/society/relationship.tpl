{capture name=header}

    {$r->jQuery()}

    <script type="text/javascript">
    // <![CDATA[
    // Set the maximum width of an image
    $(function disabler() {

        var form = $('{$form_name}');
        var me = form.getInputs('checkbox', 'identity[]');
        var checkboxes = form.getInputs('checkbox');
        var radios = form.getInputs('radio');

        if (me[0].checked) {
            checkboxes.invoke('disable');
            radios.invoke('disable');
        }
        else {
            checkboxes.invoke('enable');
            radios.invoke('enable');
        }

        me.invoke('enable');

    });
    // ]]>
    </script>

    <style type="text/css">
    label {
        margin-right: 0.5em;
    }
    .typeof {
        float: left;
        width: 140px;
        text-align: right;
    }
    </style>

{/capture}{strip}
{$r->assign('header', $smarty.capture.header)}
{include file=$r->xhtml_header}{/strip}

<div id="proselytizer">

{* Header *}
<div id="header">
    {insert name="userInfo"}
    <div class='clearboth'></div>
</div>

{* Content *}
<div id="middle">

<fieldset>
<legend>{$r->gtext.relationship}: {$nickname}</legend>

<form id="{$form_name}" action="{$r->text.form_url}" name="{$form_name}" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

<input type="hidden" name="nickname" value="{$nickname}" />
<input type="hidden" name="users_id" value="{$users_id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($users_id, $nickname)}" />
{validate id="integrity" message="integrity failure"}

{if $validate.$form_name.is_error !== false}
<p class="errorWarning">{$r->gtext.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->gtext.form_problem} :</p>
{/if}

<p onclick="disabler();">
<label class="typeof">{$r->gtext.myself} :</label>
{html_checkboxes name='identity' options=$r->getIdentity() selected=$identity}
</p>


<p>
<label class="typeof">{$r->gtext.friendship} :</label>
{html_radios name='friendship' options=$r->getFriendship() selected=$friendship}
</p>

<p>
<label class="typeof">{$r->gtext.physical} :</label>
{html_checkboxes name='physical' options=$r->getPhysical() selected=$physical}
</p>


<p>
<label class="typeof">{$r->gtext.professional} :</label>
{html_checkboxes name='professional' options=$r->getProfessional() selected=$professional}
</p>


<p>
<label class="typeof">{$r->gtext.geographical} :</label>
{html_radios name='geographical' options=$r->getGeographical() selected=$geographical}
</p>

<p>
<label class="typeof">{$r->gtext.family} :</label>
{html_radios name='family' options=$r->getFamily() selected=$family}
</p>

<p>
<label class="typeof">{$r->gtext.romantic} :</label>
{html_checkboxes name='romantic' options=$r->getRomantic() selected=$romantic}
</p>


<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->gtext.submit}" />
</p>

</form>
</fieldset>


</div>

</div>

{include file=$r->xhtml_footer}