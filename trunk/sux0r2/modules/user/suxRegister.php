<?php

/**
* suxRegister
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

class suxRegister extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @param string $key PDO dsn key
    */
    function __construct($key = null) {

        parent::__construct($key); // Call parent
        $this->tpl = new suxTemplate('user', $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language
        $this->r = new renderer(); // Renderer
        $this->r->text =& $this->gtext; // Language
        suxValidate::register_object('this', $this); // Register self to validator

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
        // Is this Openid?
        // --------------------------------------------------------------------

        if ($this->isOpenID()) {

            $this->r->bool['openid'] = true;
            $this->r->text['openid_url'] = $_SESSION['openid_url_registration'];

            // Sreg
            if (!empty($_GET['nickname'])) $_POST['nickname'] = $_GET['nickname'];
            if (!empty($_GET['email'])) $_POST['email'] = $_GET['email'];
            if (!empty($_GET['fullname'])) {
                // \w means alphanumeric characters, \W is the negated version of \w
                $tmp = mb_split("\W", $_GET['fullname']);
                $_POST['given_name'] = array_shift($tmp);
                $_POST['family_name'] = array_pop($tmp);
            }
            if (!empty($_GET['dob'])) {
                $tmp = mb_split("-", $_GET['dob']);
                $_POST['Date_Year'] = array_shift($tmp);
                $_POST['Date_Month'] = array_shift($tmp);
                $_POST['Date_Day'] = array_shift($tmp);
            }
            if (!empty($_GET['country'])) $_POST['country'] = $_GET['country'];
            if (!empty($_GET['gender'])) $_POST['gender'] = mb_strtolower($_GET['gender']);
            if (!empty($_GET['postcode'])) $_POST['postcode'] = $_GET['postcode'];
            if (!empty($_GET['language'])) $_POST['language'] = mb_strtolower($_GET['language']);
            if (!empty($_GET['timezone'])) $_POST['timezone'] = $_GET['timezone'];

        }

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

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('nickname', 'nickname', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('nickname2', 'nickname', 'invalidCharacters');
            suxValidate::register_validator('nickname3', 'nickname', 'isDuplicateNickname');
            suxValidate::register_validator('email', 'email', 'isEmail', false, false, 'trim');
            suxValidate::register_validator('email2', 'email', 'isDuplicateEmail');
            if (!$this->isOpenID()) {
                suxValidate::register_validator('password', 'password:6:-1', 'isLength');
                suxValidate::register_validator('password2', 'password:password_verify', 'isEqual');
            }

        }

        // Url
        $this->r->text['form_url'] = suxFunct::makeUrl('/user/register');

        // Defaults
        if (!$_POST) $this->tpl->assign('country', $GLOBALS['CONFIG']['COUNTRY']); // Country
        if (!$_POST) $this->tpl->assign('timezone', $GLOBALS['CONFIG']['TIMEZONE']); // Timezone
        if (!$_POST) $this->tpl->assign('language', $GLOBALS['CONFIG']['LANGUAGE']); // Languages

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $output = $this->tpl->assemble('register.tpl');
        echo $output;

    }



    /**
    * Process the form
    */
    function formProcess() {

        // --------------------------------------------------------------------
        // Sanitize
        // --------------------------------------------------------------------

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

        $clean = array_merge($clean, $_POST);

        // --------------------------------------------------------------------
        // Openid
        // --------------------------------------------------------------------

        $openid_url = null;
        if ($this->isOpenID()) {
            $clean['password'] = $this->generatePw(); // Random password
            $clean['openid_url'] = $_SESSION['openid_url_registration']; // Assign
        }

        // Sql
        $this->setUser($clean);

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
        else return false;

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