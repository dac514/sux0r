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
require_once('suxNbUser.php');

class suxEdit extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer
    public $nb; // Naive Bayesian Object

    private $users_id = null;
    private $module = 'bayes'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @param string $key PDO dsn key
    */
    function __construct($user = null) {

        parent::__construct(); // Call parent
        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new renderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Naive Bayesian
        $this->nb = new suxNbUser();

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
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {

        if(!empty($dirty['action'])) {

            $action = filter_var($dirty['action'], FILTER_SANITIZE_STRING);

            // Add Vector
            if (suxValidate::is_registered_form($action)) {
                suxValidate::connect($this->tpl);
                if (suxValidate::is_valid($dirty, $action)) {
                    suxValidate::disconnect();
                    return true;
                }
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
        // Get existing user info if available
        // --------------------------------------------------------------------

        // $u = array();
        // Get stuff from the database
        // $this->tpl->assign($u);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (empty($dirty['action']) || !suxValidate::is_registered_form($dirty['action'])) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register additional forms
            SmartyValidate::register_form('addvec');
            SmartyValidate::register_form('addcat');
            SmartyValidate::register_form('adddoc');
            SmartyValidate::register_form('remcat');
            SmartyValidate::register_form('remvec');
            SmartyValidate::register_form('remdoc');


            // Register our additional criterias
            // suxValidate::register_criteria('invalidCharacters', 'this->invalidCharacters');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            // Add vector
            suxValidate::register_validator('addvec1', 'vector', 'notEmpty', false, false, 'trim', 'addvec');
            suxValidate::register_validator('addvec2', "vector:-1:{$this->nb->getMaxVectorLength()}", 'isLength', false, false, 'trim', 'addvec');
            // Add category
            suxValidate::register_validator('addcat1', 'category', 'notEmpty', false, true, 'trim', 'addcat');
            suxValidate::register_validator('addcat2', 'vector_id', 'isInt', false, true, 'trim', 'addcat');
            suxValidate::register_validator('addcat3', "category:-1:{$this->nb->getMaxCategoryLength()}", 'isLength', false, false, 'trim', 'addcat');
            // Remove category
            suxValidate::register_validator('remcat1', 'category_id', 'isInt', false, false, 'trim', 'remcat');
            // Remove vector
            suxValidate::register_validator('remvec1', 'vector_id', 'isInt', false, false, 'trim', 'remvec');
            // Add document
            suxValidate::register_validator('adddoc1', 'document', 'notEmpty', false, true, 'trim', 'adddoc');
            suxValidate::register_validator('adddoc2', 'category_id', 'isInt', false, false, 'trim', 'adddoc');
            // Remove document
            suxValidate::register_validator('remdoc1', 'document_id', 'isInt', false, false, 'trim', 'remdoc');


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/bayes/edit');

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


        switch ($clean['action'])
        {

        case 'addvec':

            $this->nb->addVectorWithUser($clean['vector'], $_SESSION['users_id']);
            unset($clean['vector']);
            break;

        case 'remvec':

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {
                $this->nb->removeVectorWithUsers($clean['vector_id']);
            }
            unset($clean['vector_id']);
            break;

        case 'addcat':

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {
                $this->nb->addCategory($clean['category'], $clean['vector_id']);
            }
            unset($clean['category']);
            break;

        case 'remcat':

            // Security check
            if ($this->nb->isCategoryOwner($clean['category_id'], $_SESSION['users_id'])) {
                $this->nb->removeCategory($clean['category_id']);
            }
            unset($clean['category_id']);
            break;

        case 'adddoc':

            // Security check
            if ($this->nb->isTrainer($clean['category_id'], $_SESSION['users_id'])) {
                $this->nb->trainDocument($clean['document'], $clean['category_id']);
            }
            unset($clean['document']);
            break;

        case 'remdoc':

            // Security check
            if ($this->nb->isDocumentOwner($clean['document_id'], $_SESSION['users_id'])) {
                $this->nb->untrainDocument($clean['document_id']);
            }
            unset($clean['document_id']);
            break;

        }


    }


}


?>