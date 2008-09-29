{capture name=header}

    <script src="{$r->url}/includes/symbionts/scriptaculous/lib/prototype.js" type="text/javascript"></script>

    {literal}
    <script type="text/javascript">
    // <![CDATA[
    // Set the maximum width of an image
    function disabler() {

        var form = $('default');
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

    }
    Event.observe(window, 'load', function() {
        disabler();
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
        padding-top: 0.2em;
        text-align: right;
    }
    </style>
    {/literal}

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
<legend>{$r->text.relationship}: {$nickname}</legend>

<form id="default" action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

<input type="hidden" name="nickname" value="{$nickname}" />
<input type="hidden" name="users_id" value="{$users_id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($users_id, $nickname)}" />
{validate id="integrity" message="integrity failure"}

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->text.form_problem} :</p>
{/if}

<p onclick="disabler();">
<label class="typeof">{$r->text.myself} :</label>
{html_checkboxes name='identity' options=$r->getIdentity() selected=$identity}
</p>


<p>
<label class="typeof">{$r->text.friendship} :</label>
{html_radios name='friendship' options=$r->getFriendship() selected=$friendship}
</p>

<p>
<label class="typeof">{$r->text.physical} :</label>
{html_checkboxes name='physical' options=$r->getPhysical() selected=$physical}
</p>


<p>
<label class="typeof">{$r->text.professional} :</label>
{html_checkboxes name='professional' options=$r->getProfessional() selected=$professional}
</p>


<p>
<label class="typeof">{$r->text.geographical} :</label>
{html_radios name='geographical' options=$r->getGeographical() selected=$geographical}
</p>

<p>
<label class="typeof">{$r->text.family} :</label>
{html_radios name='family' options=$r->getFamily() selected=$family}
</p>

<p>
<label class="typeof">{$r->text.romantic} :</label>
{html_checkboxes name='romantic' options=$r->getRomantic() selected=$romantic}
</p>


<p>
<label>&nbsp;</label>
<input type="button" class="button" value="{$r->text.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->text.submit}" />
</p>

</form>
</fieldset>


</div>

</div>

{include file=$r->xhtml_footer}