<?php

/**
* userRegisterOpenID
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class userRegisterOpenID  {

    // Variables
    public $gtext = array();
    private $prev_skip = array();
    private $module = 'user';

    // Objects
    public $tpl;
    public $r;
    protected $user;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new suxRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator

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