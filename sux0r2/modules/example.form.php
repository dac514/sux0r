<?php

/**
* Example of a form, stripped down to the essentials.
* See bottom of file for controller.php and example.tpl
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

### die is here to avoid activating the example, remove it
die();

### __autoload() handles requires and includes

class myExample extends component {

    // Module name
    protected $module = 'example'; ### change me

	### add aditional variables here


    /**
    * Constructor
    *
    * @param int $id item id
    */
    function __construct($id = null) {

        // Declare objects
		### add objects here
        $this->r = new blogRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

		### do sanity and security checks here

        // Assign id:
        $this->id = $id;

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {
        return suxValidate::formValidate($dirty, $this->tpl);
    }


    /**
    * Build the form and show the template
    *
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild(&$dirty) {

        // --------------------------------------------------------------------
        // Pre assign template variables, maybe overwritten by &$dirty
        // --------------------------------------------------------------------

		$change_me = array(); ### change me

        if ($this->id) {

			### get the item from the database, assign it to the $change_me array.

            // Don't allow spoofing
            unset($dirty['id']);

        }

        // Assign
        $this->tpl->assign($change_me); ### change me

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
			### add validators here, examples:
            // if ($this->id) suxValidate::register_validator('integrity', 'integrity:id', 'hasIntegrity');
            // suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
			### @see: ./includes/symbionts/SmartyAddons/docs/SmartyValidate/README

        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        $this->r->text['form_url'] = suxFunct::makeUrl('/path/to/form' . $this->id); ### change me
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

		### do template stuff here

        // Template
        $this->tpl->display('example.tpl'); ### change me

    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

		### do your thing here, like saving $clean to database

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

		### do your thing here

        suxFunct::redirect(suxFunct::makeUrl('/path/to/somewhere/')); #change me

    }


}

### ---------------------------------------------------------------------------

/**
* controller.php

$foo = new myExample();
if ($foo->formValidate($_POST)) {
	$foo->formProcess($_POST);
	$foo->formSuccess();
}
else {
	$foo->formBuild($_POST);
}

*/

### ---------------------------------------------------------------------------

/**
* example.tpl

<form action="{$r->text.form_url}" name="default" method="post" enctype="multipart/form-data" accept-charset="utf-8" >
<input type="hidden" name="token" value="{$token}" />

{if $id}
<input type="hidden" name="id" value="{$id}" />
<input type="hidden" name="integrity" value="{$r->integrityHash($id)}" />
{validate id="integrity" message="integrity failure"}
{/if}

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->gtext.form_error} :</p>
{elseif $r->detectPOST()}
<p class="errorWarning">{$r->gtext.form_problem} :</p>
{/if}

<p>
{strip}
    {capture name=error}
    {validate id="title" message=$r->gtext.error_1}
    {/capture}
{/strip}
<label {if $smarty.capture.error}class="error"{/if} >{$r->gtext.title} :</label>
<input type="text" name="title" value="{$title}" />
{$smarty.capture.error}
</p>

{* ... Etc ... *}

<input type="button" class="button" value="{$r->gtext.cancel}" onclick="document.location='{$r->text.back_url}';" />
<input type="submit" class="button" value="{$r->gtext.submit}" />

</form>

*/


?>
