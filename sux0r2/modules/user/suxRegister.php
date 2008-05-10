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

        if (!empty($_POST)) $this->tpl->assign($_POST);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our extra criterias
            suxValidate::register_criteria('isDuplicateNickname', 'this->isDuplicateNickname');
            suxValidate::register_criteria('isDuplicateEmail', 'this->isDuplicateEmail');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('nickname', 'nickname', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('nickname2', 'nickname', 'isDuplicateNickname');
            suxValidate::register_validator('email', 'email', 'isEmail', false, false, 'trim');
            suxValidate::register_validator('email2', 'email', 'isDuplicateEmail');
            suxValidate::register_validator('password', 'password:password_verify', 'isEqual');

        }

        // Language
        $this->r->text = $this->gtext;

        // Url
        $this->r->text['form_url'] = suxUrl::make('/user/register');

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

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('register.tpl');
        exit;

    }


    function formProcess() {

        unset($_POST['password_verify']);

        $clean['dob'] = "{$_POST['Date_Year']}-{$_POST['Date_Month']}-{$_POST['Date_Day']}";
        unset ($_POST['Date_Year'], $_POST['Date_Month'], $_POST['Date_Day']);

        $clean = array_merge($clean, $_POST);

        new dBug($this->setUser($clean));

        exit;


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