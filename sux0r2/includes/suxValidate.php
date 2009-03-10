<?php

/**
* suxValidate
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/


/*

suxValidate extends SmartyValidate.

suxValidate protects against form spoofing and makes sure that a form submission
is intentional by adding a hidden form field with a one-time token, storing this
token in the user's SmartyValidate session.

*/

require_once(dirname(__FILE__) . '/symbionts/SmartyAddons/libs/SmartyValidate.class.php');

class suxValidate extends SmartyValidate {


    // Static class, no cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }


    /**
    * Protect against form spoofing, make sure that a form submission is valid
    * and intentional, by adding a hidden form field with a one-time token, and
    * storing this token in the user's SmartyValidate session
    *
    * @param string $form the name of the form being validated
    */
    private static function token() {

        $_SESSION['SmartyValidate'][SMARTY_VALIDATE_DEFAULT_FORM]['token'] = md5(uniqid(mt_rand(), true));
        $_smarty_obj =& SmartyValidate::_object_instance('Smarty', $_dummy);
        $_smarty_obj->assign('token', $_SESSION['SmartyValidate'][SMARTY_VALIDATE_DEFAULT_FORM]['token']);

    }


    /**
    * Override connect(): initialize the validator
    *
    * @param obj    $smarty the smarty object
    * @param string $reset reset the default form?
    */
    static function connect(&$smarty, $reset = false) {
        if(SmartyValidate::is_valid_smarty_object($smarty)) {
            SmartyValidate::_object_instance('Smarty', $smarty);
            self::register_form(SMARTY_VALIDATE_DEFAULT_FORM, $reset);
        } else {
            trigger_error("SmartyValidate: [connect] I need a valid Smarty object.");
            return false;
        }
    }


    /**
    * Override register_form(): initialize the session data
    *
    * @param string $form the name of the form being validated
    * @param string $reset reset an already registered form?
    */
    static function register_form($form, $reset = false) {

        $_ret = false;

        if (!(SmartyValidate::is_registered_form($form) && !$reset)) {

            $_SESSION['SmartyValidate'][$form] = array();
            $_SESSION['SmartyValidate'][$form]['registered_funcs']['criteria'] = array();
            $_SESSION['SmartyValidate'][$form]['registered_funcs']['transform'] = array();
            $_SESSION['SmartyValidate'][$form]['validators'] = array();
            $_SESSION['SmartyValidate'][$form]['is_error'] = false;
            $_SESSION['SmartyValidate'][$form]['is_init'] = true;
            SmartyValidate::_smarty_assign();
            self::token();
            $_ret = true;

        }
        return $_ret;

    }


    /**
    * Override is_valid(): validate the form
    *
    * @param string $formvars the array of submitted for variables
    * @param string $form the name of the form being validated
    */
    static function is_valid(&$formvars, $form = SMARTY_VALIDATE_DEFAULT_FORM) {

        // ------------------------------------------------------------------
        // Token validation
        // ------------------------------------------------------------------

        $_ret = null;
        if (empty($formvars['token']) || empty($_SESSION['SmartyValidate'][SMARTY_VALIDATE_DEFAULT_FORM]['token'])) {
            trigger_error("SmartyValidate: [token] is not set.");
            $_ret = false;
        }
        else if ($formvars['token'] != $_SESSION['SmartyValidate'][SMARTY_VALIDATE_DEFAULT_FORM]['token']) {
            $_ret = false;
        }
        unset($formvars['token']);
        self::token();
        if ($_ret === false) {
            // We need this disconnect() here to fix problem with multiple 'default'
            // forms open in multiple browser tabs, otherwise shit is broken
            SmartyValidate::disconnect();
            return false;
        }


        // ------------------------------------------------------------------
        // And now, back to your regular scheduled program
        // ------------------------------------------------------------------

        static $_is_valid = array();

        if(isset($_is_valid[$form])) {
            // already validated the form
            return $_is_valid[$form];
        }

        $_smarty_obj =& SmartyValidate::_object_instance('Smarty', $_dummy);
        if(!SmartyValidate::is_valid_smarty_object($_smarty_obj)) {
            trigger_error("SmartyValidate: [is_valid] No valid smarty object, call connect() first.");
            return false;
        }

        if(!SmartyValidate::is_registered_form($form)) {
            trigger_error("SmartyValidate: [is_valid] form '$form' is not registered.");
            return false;
        } elseif ($_SESSION['SmartyValidate'][$form]['is_init']) {
            // first run, skip validation
            return false;
        } elseif (count($_SESSION['SmartyValidate'][$form]['validators']) == 0) {
            // nothing to validate
            return true;
        }

        // check for failed fields
        $_failed_fields = SmartyValidate::_failed_fields($formvars, $form);
        $_ret = is_array($_failed_fields) && count($_failed_fields) == 0;

        // set validation state of form
        $_SESSION['SmartyValidate'][$form]['is_error'] = !$_ret;

        $_is_valid[$form] = $_ret;

        return $_ret;

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @param object smarty template
    * @param string $form the name of the form being validated
    * @return bool
    */
    static function formValidate(&$dirty, $tpl, $form = SMARTY_VALIDATE_DEFAULT_FORM) {

        if(!empty($dirty) && SmartyValidate::is_registered_form($form)) {
            // Validate
            self::connect($tpl);
            if(self::is_valid($dirty, $form)) {
                SmartyValidate::disconnect();
                return true;
            }
        }
        return false;

    }


}


// -------------------------------------------------------------------------
// Smarty validate functions
// -------------------------------------------------------------------------

/**
* Test if values maintain integrity
*
* @global string $CONFIG['SALT']
* @param string $value the value being tested
* @param boolean $empty if field can be empty
* @param array params validate parameter values
* @param array formvars form var values
*/
function smarty_validate_criteria_hasIntegrity($value, $empty, &$params, &$formvars) {

    $compare = '';
    foreach ($params as $key => $val) {
        if ($key == 'field') {
            // get rid of this
            // it (should be) a hidden field that shouldn't be remembered
            unset($formvars[$val]);
            continue;
        }
        elseif (preg_match('/^field[2-7]$/', $key)) {
            // Up to 6 variables can be hashed
            // see suxRenderer::integrityHash()
            $compare .= $formvars[$val];
        }
    }
    $compare = md5($compare . $GLOBALS['CONFIG']['SALT']);

    if ($value != $compare) return false;
    else {

        return true;
    }

}


?>