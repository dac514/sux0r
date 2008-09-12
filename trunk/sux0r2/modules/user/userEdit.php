<?php

/**
* userEdit
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
require_once('userRenderer.php');

class userEdit {

    // Variables
    public $gtext = array(); // Language
    private $mode = 'register';
    private $users_id = null;
    private $module = 'user';        
    
    // Objects
    public $tpl;
    public $r;
    private $user;


    /**
    * Constructor
    *
    */
    function __construct($mode = 'register', $user = null) {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new userRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // -------------------------------------------------------------------
        // Edit mode
        // -------------------------------------------------------------------

        if ($mode == 'edit') {

            // Redirect if invalid
            if ($this->user->loginCheck(suxfunct::makeUrl('/user/register'))) {
                $this->mode = 'edit';
            }

            if ($user != $_SESSION['nickname']) {

                // TODO:
                // Security check
                // Only an administrator can modify other users

                $u = $this->user->getUserByNickname($user);
                if ($u) $this->users_id = $u['users_id'];

            }

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
    * @param array $filthy reference to unverified $_GET
    */
    function formBuild(&$dirty, &$filthy) {

        // --------------------------------------------------------------------
        // Get existing user info if available
        // --------------------------------------------------------------------

        $u = array();

        if ($this->isOpenID()) {

            // OpenID Registration
            $this->r->bool['openid'] = true;
            $this->r->text['openid_url'] = $_SESSION['openid_url_registration'];

            // Sreg
            if (!empty($filthy['nickname'])) $u['nickname'] = $filthy['nickname'];
            if (!empty($filthy['email'])) $u['email'] = $filthy['email'];
            if (!empty($filthy['fullname'])) {
                // \w means alphanumeric characters, \W is the negated version of \w
                $tmp = mb_split("\W", $filthy['fullname']);
                $u['given_name'] = array_shift($tmp);
                $u['family_name'] = array_pop($tmp);
            }
            if (!empty($filthy['dob'])) {
                $tmp = mb_split("-", $filthy['dob']);
                $u['Date_Year'] = array_shift($tmp);
                $u['Date_Month'] = array_shift($tmp);
                $u['Date_Day'] = array_shift($tmp);
            }
            if (!empty($filthy['country'])) $u['country'] = mb_strtolower($filthy['country']);
            if (!empty($filthy['gender'])) $u['gender'] = mb_strtolower($filthy['gender']);
            if (!empty($filthy['postcode'])) $u['postcode'] = $filthy['postcode'];
            if (!empty($filthy['language'])) $u['language'] = mb_strtolower($filthy['language']);
            if (!empty($filthy['timezone'])) $u['timezone'] = $filthy['timezone'];

        }
        elseif ($this->mode == 'edit') {

            // Edit mode

            $u = $this->user->getUser($this->users_id, true);

            // Unset
            unset($u['password']);
            unset($u['root']);

            // Dob
            if (!empty($u['dob'])) {
                $tmp = mb_split("-", $u['dob']);
                $u['Date_Year'] = array_shift($tmp);
                $u['Date_Month'] = array_shift($tmp);
                $u['Date_Day'] = array_shift($tmp);
            }
            unset($u['dob']);

            // Country
            if (!empty($u['country'])) {
                $u['country'] = mb_strtolower($u['country']);
            }

            // Don't allow spoofing
            unset($dirty['nickname']);

        }

        // Assign user
        $this->tpl->assign($u);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            suxValidate::register_criteria('invalidCharacters', 'this->invalidCharacters');
            suxValidate::register_criteria('isDuplicateNickname', 'this->isDuplicateNickname');
            suxValidate::register_criteria('isReservedNickname', 'this->isReservedNickname');
            suxValidate::register_criteria('isDuplicateEmail', 'this->isDuplicateEmail');
            suxValidate::register_criteria('isValidCaptcha', 'this->isValidCaptcha');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('nickname', 'nickname', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('nickname2', 'nickname', 'invalidCharacters');
            suxValidate::register_validator('nickname3', 'nickname', 'isDuplicateNickname');
            suxValidate::register_validator('nickname4', 'nickname', 'isReservedNickname');
            suxValidate::register_validator('email', 'email', 'isEmail', false, false, 'trim');
            suxValidate::register_validator('email2', 'email', 'isDuplicateEmail');
            if ($this->mode == 'edit') suxValidate::register_validator('integrity', 'integrity:nickname', 'hasIntegrity');



            // --------------------------------------------------------------------
            // Validators with special conditions
            // --------------------------------------------------------------------

            // Passwords
            if (!$this->isOpenID()) {

                if ($this->mode == 'edit') {
                    // Empty is ok in edit mode
                    suxValidate::register_validator('password', 'password:6:-1', 'isLength', true, false, 'trim');
                    suxValidate::register_validator('password2', 'password:password_verify', 'isEqual', true);
                }
                else {
                    suxValidate::register_validator('password', 'password:6:-1', 'isLength', false, false, 'trim');
                    suxValidate::register_validator('password2', 'password:password_verify', 'isEqual');
                }

            }

            // Captcha
            if ($this->mode != 'edit') suxValidate::register_validator('captcha', 'captcha', 'isValidCaptcha');

        }


        // --------------------------------------------------------------------
        // DONE: Form Logic
        // --------------------------------------------------------------------

        // Defaults
        if (!$this->tpl->get_template_vars('country'))
            $this->tpl->assign('country', $GLOBALS['CONFIG']['COUNTRY']); // Country
        if (!$this->tpl->get_template_vars('timezone'))
            $this->tpl->assign('timezone', $GLOBALS['CONFIG']['TIMEZONE']); // Timezone
        if (!$this->tpl->get_template_vars('language'))
            $this->tpl->assign('language', $GLOBALS['CONFIG']['LANGUAGE']); // Languages


        // Additional variables

        $this->r->text['form_url'] = suxFunct::makeUrl('/user/register'); // Register

        // Overrides for edit mode
        if ($this->mode == 'edit') {
            $this->r->text['form_url'] = suxFunct::makeUrl('/user/edit/' . @$u['nickname']); // Edit
            $this->r->bool['edit'] = true;
        }

        $this->r->text['back_url'] = suxFunct::getPreviousURL($GLOBALS['CONFIG']['PREV_SKIP']);

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('edit.tpl');

    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        // Captcha
        unset($_SESSION['captcha']);
        unset($clean['captcha']);

        // Redundant password field
        unset($clean['password_verify']);

        // Birthday
        if (!empty($clean['Date_Year']) && !empty($clean['Date_Month']) && !empty($clean['Date_Day'])) {
            $clean['dob'] = "{$clean['Date_Year']}-{$clean['Date_Month']}-{$clean['Date_Day']}";
        }
        if (isset($clean['dob']) && !filter_var($clean['dob'], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/")))) {
            $clean['dob'] = null;
        }
        unset ($clean['Date_Year'], $clean['Date_Month'], $clean['Date_Day']);
        if (isset($clean['dob'])) $clean['dob'] = date('Y-m-d', strtotime($clean['dob'])); // Sanitize
        else $clean['dob'] = null;


        // --------------------------------------------------------------------
        // Edit Mode
        // --------------------------------------------------------------------

        if ($this->mode == 'edit') {

            if ($clean['nickname'] != $_SESSION['nickname']) {
                // TODO:
                // Security check
                // Only an administrator can modify other users
            }

            // Get users_id
            $u = $this->user->getUserByNickname($clean['nickname']);
            if (!$u) throw new Exception('Invalid user');
            $id = $u['users_id'];

        }

        // --------------------------------------------------------------------
        // Openid
        // --------------------------------------------------------------------

        if ($this->isOpenID()) {
            $clean['password'] = $this->user->generatePw(); // Random password
            $clean['openid_url'] = $_SESSION['openid_url_registration']; // Assign
        }

        // --------------------------------------------------------------------
        // SQL
        // --------------------------------------------------------------------

        if (isset($id) && filter_var($id, FILTER_VALIDATE_INT)) $this->user->saveUser($clean, $id);
        else $this->user->saveUser($clean);

        // --------------------------------------------------------------------
        // Cleanup
        // --------------------------------------------------------------------

        unset($_SESSION['openid_url_registration'], $_SESSION['openid_url_integrity']);

        // Clear approptiate template caches
        $this->tpl->clear_cache('profile.tpl', $clean['nickname']);

        // Reset session
        if ($this->mode == 'edit' && $clean['nickname'] == $_SESSION['nickname']) {
            $this->user->setSession($clean['nickname'], true);
        }

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Edit mode?
        if ($this->mode == 'edit') {

            $this->r->bool['edit'] = true;
            $this->r->text['back_url'] = suxFunct::getPreviousURL($GLOBALS['CONFIG']['PREV_SKIP']);

        }

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('success.tpl');

    }


    /**
    * for suxValidate, check for invalid characters
    *
    * @return bool
    */
    function invalidCharacters($value, $empty, &$params, &$formvars) {

        if (empty($formvars['nickname'])) return false;

        if (!preg_match('/^(\w|\-)+$/', $formvars['nickname'])) return false; // Invalid characters
        else return true;

    }



    /**
    * for suxValidate, check if a duplicate nickname exists
    *
    * @return bool
    */
    function isDuplicateNickname($value, $empty, &$params, &$formvars) {

        if (empty($formvars['nickname'])) return false;

        $tmp = $this->user->getUserByNickname($formvars['nickname']);
        if ($tmp === false ) return true; // No duplicate found

        if($this->mode == 'edit') {
            $u = $this->user->getUser($this->users_id);
            if ($formvars['nickname'] == $u['nickname']) {
                // This is a user editing themseleves, this is OK
                return true;
            }
        }

        return false; // Fail

    }

    /**
    * for suxValidate, check if nickname is 'nobody'
    *
    * @return bool
    */
    function isReservedNickname($value, $empty, &$params, &$formvars) {

        if (empty($formvars['nickname'])) return false;

        if (mb_strtolower($formvars['nickname']) == 'nobody') return false; // Fail
        else return true;

    }


    /**
    * for suxValidate, check if a duplicate email exists
    *
    * @return bool
    */
    function isDuplicateEmail($value, $empty, &$params, &$formvars) {

        if (empty($formvars['email'])) return false;

        $tmp = $this->user->getUserByEmail($formvars['email']);
        if ($tmp === false ) return true; // No duplicate found

        if($this->mode == 'edit') {
            $u = $this->user->getUser($this->users_id);
            if ($formvars['email'] == $u['email']) {
                // This is a user editing themseleves, this is OK
                return true;
            }
        }

        return false; // Fail

    }


    /**
    * for suxValidate, check for matching Captcha
    *
    * @return bool
    */
    function isValidCaptcha($value, $empty, &$params, &$formvars) {

        if (empty($formvars['captcha'])) return false;
        if (empty($_SESSION['captcha'])) return false;

        if (mb_strtolower($_SESSION['captcha']) == mb_strtolower($formvars['captcha'])) return true;
        else return false;

    }


    /**
    * Check if openid registration is set
    *
    * @return bool
    */
    private function isOpenID() {

        // These session variables are set by the openid module

        if (
            !empty($_SESSION['openid_url_registration']) && !empty($_SESSION['openid_url_integrity']) &&
            (md5($_SESSION['openid_url_registration'] . @$GLOBALS['CONFIG']['SALT']) == $_SESSION['openid_url_integrity']) &&
            filter_var($_SESSION['openid_url_registration'], FILTER_VALIDATE_URL)
            ) return true;

        else return false;

    }


}


?>