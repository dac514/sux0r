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
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');
require_once(dirname(__FILE__) . '/../../includes/suxUrl.php');

class suxRegister extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    function __construct($dbKey = null) {

        // --------------------------------------------------------------------
        // Sanity Check
        // --------------------------------------------------------------------

        if (!isset($GLOBALS['CONFIG'])) {
            die("Something is wrong, can't initialize without configuration.");
        }

        // --------------------------------------------------------------------
        // Go
        // --------------------------------------------------------------------

        parent::__construct($dbKey); // Call parent

        $this->tpl = new suxTemplate('user', $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language
        $this->r = new suxRenderer(); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator

    }


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


    function formBuild() {

        // Language
        $this->r->text = $this->gtext;

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
            suxValidate::register_criteria('isDuplicateNickname', 'this->isDuplicateNickname');
            suxValidate::register_criteria('isDuplicateEmail', 'this->isDuplicateEmail');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('nickname', 'nickname', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('nickname2', 'nickname', 'isDuplicateNickname');
            suxValidate::register_validator('email', 'email', 'isEmail', false, false, 'trim');
            suxValidate::register_validator('email2', 'email', 'isDuplicateEmail');
            if (!$this->isOpenID()) suxValidate::register_validator('password', 'password:password_verify', 'isEqual');

        }


        // Url
        $this->r->text['form_url'] = suxUrl::make('/user/register');

        // Countries
        $this->r->text['countries'][''] = '---';
        $this->r->text['countries'] = array_merge($this->r->text['countries'], suxFunct::getCountries());
        foreach ($this->r->text['countries'] as $key => $val) {
            if (isset($this->gtext["{$key}2"])) $this->r->text['countries'][$key] = $this->gtext["{$key}2"];
        }

        // Genders
        $this->r->text['genders'] = array(
            'm' => $this->gtext['male'],
            'f' => $this->gtext['female'],
            );

        // Timezones
        $tz = timezone_identifiers_list();
        $this->r->text['timezones'][''] = '---';
        foreach ($tz as $val) {
            $this->r->text['timezones'][$val] = $val;
        }

        // Languages
        $this->r->text['languages'][''] = '---';
        $this->r->text['languages'] = array_merge($this->r->text['languages'], suxFunct::getLanguages());
        foreach ($this->r->text['languages'] as $key => $val) {
            if (isset($this->gtext[$key])) $this->r->text['languages'][$key] = $this->gtext[$key];
        }

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('register.tpl');

    }


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


    function formSuccess() {

        echo 'Success!';

    }


    function isOpenID() {

        // These session variables are set by the openid module

        if (
            !empty($_SESSION['openid_url_registration']) && !empty($_SESSION['openid_url_integrity']) &&
            (md5($_SESSION['openid_url_registration'] . @$GLOBALS['CONFIG']['SALT']) == $_SESSION['openid_url_integrity']) &&
            filter_var($_SESSION['openid_url_registration'], FILTER_VALIDATE_URL)
            ) return true;

        else return false;

    }


    // suxValidate
    function isDuplicateNickname($value, $empty, &$params, &$formvars) {

        if (empty($formvars['nickname'])) return false;

        $tmp = $this->getUserByNickname($formvars['nickname']);
        if ($tmp === false ) return true; // No duplicate found
        else return false;

    }

    // suxValidate
    function isDuplicateEmail($value, $empty, &$params, &$formvars) {

        if (empty($formvars['email'])) return false;

        $tmp = $this->getUserByEmail($formvars['email']);
        if ($tmp === false ) return true; // No duplicate found
        else return false;

    }




}


?>