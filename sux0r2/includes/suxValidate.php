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


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @param object smarty template
    * @param string $form the name of the form being validated
    * @return bool
    */
    static function formValidate(&$dirty, $tpl, $form = null) {

        if(!isset($form)) $form = self::$form;

        if(!empty($dirty) && SmartyValidate::is_registered_form($form)) {

            // Check token
            if (!empty($dirty['token'])) {
                if (!in_array($dirty['token'], $_SESSION['_sux0r_tokens'])) {
                    return false;
                }
            }
            unset($dirty['token']);

            // Validate
            self::connect($tpl);

            if(self::is_valid($dirty, $form)) {
                SmartyValidate::disconnect(true);
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