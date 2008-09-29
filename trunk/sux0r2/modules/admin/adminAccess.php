<?php

/**
* adminAccess
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
require_once('adminRenderer.php');

class adminAccess {

    // Variables
    private $nickname;
    private $users_id;
    private $root;
    private $banned;
    public $gtext = array(); // Language
    private $module = 'admin';

    // Objects
    public $tpl;
    public $r;
    private $user;


    /**
    * Constructor
    *
    */
    function __construct($nickname) {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new adminRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) suxFunct::redirect(suxFunct::makeUrl('/home'));


        $tmp = $this->user->getUserByNickname($nickname);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user

        $this->nickname = $nickname;
        $this->users_id = $tmp['users_id'];
        $this->root = $tmp['root'];
        $this->banned = $tmp['banned'];

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
    function formBuild(&$dirty) {

        // --------------------------------------------------------------------
        // Pre assign template variables, maybe overwritten by &$dirty
        // --------------------------------------------------------------------

        $this->tpl->assign('root', $this->root);
        $this->tpl->assign('banned', $this->banned);

        // Dynamically create access dropdowns
        $myOptions = array();
        $mySelect = array();
        $tmp = array('0' => '---');
        foreach($GLOBALS['CONFIG']['ACCESS'] as $key => $val) {

            // Manipulate the array into something Smarty can use
            $tmp2 = $tmp;
            $val = array_flip($val);
            foreach ($val as $key2 => $val2) $tmp2[$key2] = $val2;
            $val = $tmp2;

            $myOptions[$key] = $val;
            $mySelect[$key] = $this->user->getAccess($key, $this->users_id);

        }

        if (!empty($dirty)) foreach ($dirty as $key => $val) {
            // Use the submitted values
            if (isset($mySelect[$key])) $mySelect[$key] = $val;
        }

        $this->tpl->assign('myOptions', $myOptions);
        $this->tpl->assign('mySelect', $mySelect);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');
        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        $this->tpl->assign('access', $GLOBALS['CONFIG']['ACCESS']);
        $this->tpl->assign('nickname', $this->nickname);
        $this->tpl->assign('users_id', $this->users_id);
        $this->r->text['form_url'] = suxFunct::makeUrl("/admin/access/{$this->nickname}");
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        if ($this->users_id == $_SESSION['users_id']) $this->tpl->assign('disabled', 'disabled="disabled"');

        $this->r->title .= " | {$this->r->text['edit_access']}";

        // Display template
        $this->tpl->display('access.tpl');


    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // Security check
        if (!$this->user->isRoot()) suxFunct::redirect(suxFunct::makeUrl('/home'));

        // Root
        if (isset($clean['root'])) $this->user->root($this->users_id);
        elseif ($this->users_id != $_SESSION['users_id']) {
            // Don't allow a user to unroot themselves
            $this->user->unroot($this->users_id);
        }

        // Banned
        if (!isset($clean['banned'])) $this->user->unban($this->users_id);
        elseif ($this->users_id != $_SESSION['users_id']) {
            // Don't allow a user to ban themselves
            $this->user->ban($this->users_id);
        }

        foreach($GLOBALS['CONFIG']['ACCESS'] as $key => $val) {
            if (isset($clean[$key])) {
                if ($clean[$key]) $this->user->saveAccess($key, $clean[$key], $this->users_id);
                else $this->user->removeAccess($key, $this->users_id);
            }
        }

        // Log, private
        $this->user->log("sux0r::adminAccess() users_id: {$this->users_id} ", $_SESSION['users_id'], 1);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::getPreviousURL());

    }




}


?>