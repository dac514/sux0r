<?php

/**
* userRegisterOpenID
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class userRegisterOpenID extends component {

    // Module name
    protected $module = 'user';

    // Form name
    protected $form_name = 'userRegisterOpenID';

    // Var
    private $prev_skip = array();


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->r = new userRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // This module can fallback on user/openid module
        foreach ($GLOBALS['CONFIG']['PREV_SKIP'] as $val) {
            if (mb_strpos($val, 'user/openid') === false)
                $this->prev_skip[] = $val;
        }

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

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            suxValidate::register_criteria('isDuplicateOpenIDUrl', 'this->isDuplicateOpenIDUrl');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');
            suxValidate::register_validator('url3', 'url', 'isDuplicateOpenIDUrl');

        }

        // Title
        $this->r->title .= " | {$this->r->gtext['openid_reg']}";

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/user/register/openid');
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_skip);

        // Template
        $this->tpl->display('register_openid.tpl');

    }



    /**
    * Redirect to openid module
    *
    * @param array $clean reference to validated $_POST
    */
    function formHandoff(&$clean) {

        $q = array('openid.mode' => 'login', 'openid_url' => $clean['url']);
        $url = suxFunct::makeUrl('/openid/register/openid', $q);
        suxFunct::redirect($url);

    }


    /**
    * for suxValidate, check if a duplicate openid url exists
    *
    * @return bool
    */
    function isDuplicateOpenIDUrl($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;

        $user = $this->user->getUserByOpenID(suxFunct::canonicalizeUrl($formvars['url']));

        if ($user) return false; // Duplicate found, fail
        else return true;

    }


}


?>