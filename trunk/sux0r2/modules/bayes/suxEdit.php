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

    private $users_id = null;

    private $module = 'bayes'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @param string $key PDO dsn key
    */
    function __construct($user = null, $key = null) {

        parent::__construct($key); // Call parent
        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new renderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        $this->loginCheck(suxfunct::makeUrl('/user/register'));

        if ($user != $_SESSION['nickname']) {

            // TODO:
            // Security check
            // Only an administrator can modify other users

            $u = $this->getUserByNickname($user);
            if ($u) $this->users_id = $u['users_id'];

        }


    }


    /**
    * Validate the form
    *
    * @return bool
    */
    function formValidate() {

        if(!empty($_POST) && suxValidate::is_registered_form()) {
            // TODO:
            // || !suxValidate::is_registered_form('myform')
            // || !suxValidate::is_registered_form('myform2')
            // || !suxValidate::is_registered_form('myform3')

            // Validate
            suxValidate::connect($this->tpl);
            if(suxValidate::is_valid($_POST)) {
                // TODO:
                // || !suxValidate::is_valid($_POST, 'myform')
                // || !suxValidate::is_valid($_POST, 'myform2')
                // || !suxValidate::is_valid($_POST, 'myform3')
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
        // Get stuff from the database
        $this->tpl->assign($u);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($_POST)) $this->tpl->assign($_POST);
        else suxValidate::disconnect();


        if (!suxValidate::is_registered_form()) {
            // || !suxValidate::is_registered_form('myform')
            // || !suxValidate::is_registered_form('myform2')
            // || !suxValidate::is_registered_form('myform3')

            suxValidate::connect($this->tpl, true); // Reset connection

            // TODO:
            // SmartyValidate::register_form('default'); // Automatic
            // SmartyValidate::register_form('myform');
            // SmartyValidate::register_form('myform2');
            // SmartyValidate::register_form('myform3');

            // Register our additional criterias
            // suxValidate::register_criteria('invalidCharacters', 'this->invalidCharacters');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            // suxValidate::register_validator('nickname', 'nickname', 'notEmpty', false, false, 'trim');
            // suxValidate::register_validator('nickname2', 'nickname', 'invalidCharacters');

        }


        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/bayes/edit'); // Register
        // $this->r->text['back_url'] = $this->getPreviousURL();

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

        // Todo

        // --------------------------------------------------------------------
        // SQL
        // --------------------------------------------------------------------

        // $this->addCategory($clean);


    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Success

    }



}


?>