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
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('renderer.php');

class suxEdit {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    private $prev_url_preg = '#^blog/[edit]#i';
    private $user; // suxUser
    private $module = 'blog'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @param string $key PDO dsn key
    */
    function __construct($id = null) {

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new renderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxuser();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));
         
        if (filter_var($id, FILTER_VALIDATE_INT)) {
            // TODO: 
            // Verfiy that we are allowed to edit this
        }


    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {

        if(!empty($dirty) && suxValidate::is_registered_form()) {
            // Validate
            suxValidate::connect($this->tpl);
            if(suxValidate::is_valid($dirty)) {
                suxValidate::disconnect();
                return true;
            }
        }
        return false;

    }


    /**
    * Build the form and show the template
    *
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild(&$dirty) {

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            //suxValidate::register_criteria('invalidShare', 'this->invalidShare', 'sharevec');
            //suxValidate::register_criteria('userExists', 'this->userExists', 'sharevec');
            
            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/edit');
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);


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
        
        // Do stuff

    }
    

}


?>