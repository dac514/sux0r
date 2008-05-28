<?php

/**
* suxEdit
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

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('renderer.php');

class suxEdit extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    private $mode = 'register';
    private $users_id = null;

    private $module = 'user'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @param string $key PDO dsn key
    */
    function __construct($mode = 'register', $user = null, $key = null) {

        parent::__construct($key); // Call parent
        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language
        $this->r = new renderer($this->module); // Renderer
        $this->r->text =& $this->gtext; // Language
        suxValidate::register_object('this', $this); // Register self to validator

        // -------------------------------------------------------------------
        // Edit mode
        // -------------------------------------------------------------------

        if ($mode == 'edit') {

            // Redirect if invalid
            if ($this->loginCheck(suxfunct::makeUrl('/user/register'))) {
                $this->mode = 'edit';
            }

            if ($user != $_SESSION['nickname']) {

                // TODO:
                // Security check
                // Only an administrator can modify other users

                $u = $this->getUserByNickname($user);
                if ($u) $this->users_id = $u['users_id'];

            }

        }

    }


    /**
    * Validate the form
    *
    * @return bool
    */
    function formValidate() {

        if(!empty($_POST) && suxValidate::is_registered_form()) {
            // Validate
            suxValidate::connect($this->tpl);
            if(suxValidate::is_valid($_POST)) {
                suxValidate::disconnect();
                return true;
            }
        }
        return false;

    }


    /**
    * Build the form and show the template
    */
    function formBuild() {

        // --------------------------------------------------------------------
        // Get existing user info if available
        // --------------------------------------------------------------------

        $u = array();

        if ($this->isOpenID()) {

            // OpenID Registration

            $this->r->bool['openid'] = true;
            $this->r->text['openid_url'] = $_SESSION['openid_url_registration'];

            // Sreg

            if (!empty($_GET['nickname'])) $u['nickname'] = $_GET['nickname'];
            if (!empty($_GET['email'])) $u['email'] = $_GET['email'];
            if (!empty($_GET['fullname'])) {
                // \w means alphanumeric characters, \W is the negated version of \w
                $tmp = mb_split("\W", $_GET['fullname']);
                $u['given_name'] = array_shift($tmp);
                $u['family_name'] = array_pop($tmp);
            }
            if (!empty($_GET['dob'])) {
                $tmp = mb_split("-", $_GET['dob']);
                $u['Date_Year'] = array_shift($tmp);
                $u['Date_Month'] = array_shift($tmp);
                $u['Date_Day'] = array_shift($tmp);
            }
            if (!empty($_GET['country'])) $u['country'] = mb_strtolower($_GET['country']);
            if (!empty($_GET['gender'])) $u['gender'] = mb_strtolower($_GET['gender']);
            if (!empty($_GET['postcode'])) $u['postcode'] = $_GET['postcode'];
            if (!empty($_GET['language'])) $u['language'] = mb_strtolower($_GET['language']);
            if (!empty($_GET['timezone'])) $u['timezone'] = $_GET['timezone'];

        }
        elseif ($this->mode == 'edit') {

            // Edit mode

            $u = $this->getUser($this->users_id, true);

            // Unset
            unset($u['password']);
            unset($u['accesslevel']);
            unset($u['pavatar']);
            unset($u['microid']);

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

        }

        // Assign user
        $this->tpl->assign($u);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($_POST)) $this->tpl->assign($_POST);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            suxValidate::register_criteria('invalidCharacters', 'this->invalidCharacters');
            suxValidate::register_criteria('isDuplicateNickname', 'this->isDuplicateNickname');
            suxValidate::register_criteria('isDuplicateEmail', 'this->isDuplicateEmail');
            suxValidate::register_criteria('isValidCaptcha', 'this->isValidCaptcha');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('nickname', 'nickname', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('nickname2', 'nickname', 'invalidCharacters');
            suxValidate::register_validator('nickname3', 'nickname', 'isDuplicateNickname');
            suxValidate::register_validator('email', 'email', 'isEmail', false, false, 'trim');
            suxValidate::register_validator('email2', 'email', 'isDuplicateEmail');

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

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('edit.tpl');

    }



    /**
    * Process the form
    */
    function formProcess() {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

        // Captcha
        unset($_SESSION['captcha']);
        unset($_POST['captcha']);

        // Redundant password field
        unset($_POST['password_verify']);

        // Birthday
        $clean['dob'] = null;
        if (!empty($_POST['Date_Year']) && !empty($_POST['Date_Month']) && !empty($_POST['Date_Day'])) {
            $clean['dob'] = "{$_POST['Date_Year']}-{$_POST['Date_Month']}-{$_POST['Date_Day']}";
        }
        if (!filter_var($clean['dob'], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => "/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/")))) {
            unset ($clean['dob']);
        }
        unset ($_POST['Date_Year'], $_POST['Date_Month'], $_POST['Date_Day']);

        // --------------------------------------------------------------------
        // Edit Mode
        // --------------------------------------------------------------------

        if ($this->mode == 'edit') {

            if ($_POST['nickname'] != $_SESSION['nickname']) {
                // TODO:
                // Security check
                // Only an administrator can modify other users
            }

            // Get users_id
            $u = $this->getUserByNickname($_POST['nickname']);
            if (!$u) throw new Exception('Invalid user');
            $id = $u['users_id'];

            // Clear approptiate template caches
            $cache_id = $_POST['nickname'];
            $this->tpl->clear_cache('profile.tpl', $cache_id);

        }

        // --------------------------------------------------------------------
        // Openid
        // --------------------------------------------------------------------

        if ($this->isOpenID()) {
            $clean['password'] = $this->generatePw(); // Random password
            $clean['openid_url'] = $_SESSION['openid_url_registration']; // Assign
        }

        // --------------------------------------------------------------------
        // SQL
        // --------------------------------------------------------------------

        $clean = array_merge($clean, $_POST);

        if (isset($id) && filter_var($id, FILTER_VALIDATE_INT)) $this->setUser($clean, $id);
        else $this->setUser($clean);

        // Unset
        unset($_SESSION['openid_url_registration'], $_SESSION['openid_url_integrity']);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        echo 'Success!';

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

        $tmp = $this->getUserByNickname($formvars['nickname']);
        if ($tmp === false ) return true; // No duplicate found

        if($this->mode == 'edit') {
            $u = $this->getUser($this->users_id);
            if ($formvars['nickname'] == $u['nickname']) {
                // This is a user editing themseleves, this is OK
                return true;
            }
        }

        return false; // Fail

    }


    /**
    * for suxValidate, check if a duplicate email exists
    *
    * @return bool
    */
    function isDuplicateEmail($value, $empty, &$params, &$formvars) {

        if (empty($formvars['email'])) return false;

        $tmp = $this->getUserByEmail($formvars['email']);
        if ($tmp === false ) return true; // No duplicate found

        if($this->mode == 'edit') {
            $u = $this->getUser($this->users_id);
            if ($formvars['email'] == $u['email']) {
                // This is a user editing themseleves, this is OK
                return true;
            }
        }

        return false; // Fail

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


}


?>