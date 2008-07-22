<?php

/**
* bayesEdit
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

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('bayesRenderer.php');
require_once('bayesUser.php');

class bayesEdit {

    // Variables
    public $caches = array('blog', 'feeds'); // Modules that cache bayes interfaces 
    public $gtext = array();
    private $module = 'bayes'; // Module
    
    // Objects
    public $tpl;
    public $r;
    public $nb;
    private $user;
    private $link;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new bayesRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        $this->user = new suxuser();
        $this->nb = new bayesUser();
        $this->link = new suxLink();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));


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
            suxValidate::register_form('addvec');
            suxValidate::register_form('addcat');
            suxValidate::register_form('adddoc');
            suxValidate::register_form('remcat');
            suxValidate::register_form('remvec');
            suxValidate::register_form('remdoc');
            suxValidate::register_form('sharevec');
            suxValidate::register_form('unsharevec');


            // Register our additional criterias
            suxValidate::register_criteria('invalidShare', 'this->invalidShare', 'sharevec');
            suxValidate::register_criteria('userExists', 'this->userExists', 'sharevec');

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
            // Share vector
            suxValidate::register_validator('sharevec1', 'vector_id', 'isInt', false, false, 'trim', 'sharevec');
            suxValidate::register_validator('sharevec2', 'users_id', 'isInt', false, false, 'trim', 'sharevec');
            suxValidate::register_validator('sharevec3', 'trainer:1:1', 'isRange', true, false, 'trim', 'sharevec');
            suxValidate::register_validator('sharevec4', 'owner:1:1', 'isRange', true, false, 'trim', 'sharevec');
            suxValidate::register_validator('sharevec5', 'users_id', 'invalidShare', true, false, 'trim', 'sharevec');
            suxValidate::register_validator('sharevec6', 'users_id', 'userExists', true, false, 'trim', 'sharevec');
            // Unshare vector
            suxValidate::register_validator('unsharevec1', 'unshare', 'dummyValid', false, false, null, 'unsharevec');

        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/bayes');

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
        // Clear user caches
        // --------------------------------------------------------------------
        
        foreach ($this->caches as $module) {
            // clear all caches with "nickname" as the first cache_id group   
            $tpl = new suxTemplate($module);
            $tpl->clear_cache(null, "{$_SESSION['nickname']}");     
        }
        
        // --------------------------------------------------------------------
        // Action
        // --------------------------------------------------------------------        

        switch ($clean['action'])
        {

        case 'addvec':

            $this->nb->addVectorWithUser($clean['vector'], $_SESSION['users_id']);
            unset($clean['vector']);
            break;

        case 'remvec':

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {
                // Remove any links to vector documents in associated link tables
                $links = $this->link->getLinkTables('bayes');
                foreach ($this->nb->getDocumentsByVector($clean['vector_id']) as $key => $val) {
                    foreach ($links as $tmp) {
                        $this->link->deleteLink($tmp, 'bayes_documents', $key);
                    }
                }
                // Remove vector
                $this->nb->removeVector($clean['vector_id']);
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
                // Remove any links to category documents in associated link tables
                $links = $this->link->getLinkTables('bayes');
                foreach ($this->nb->getDocumentsByCategory($clean['category_id']) as $key => $val) {
                    foreach ($links as $tmp) {
                        $this->link->deleteLink($tmp, 'bayes_documents', $key);
                    }
                }
                // Remove category
                $this->nb->removeCategory($clean['category_id']);
            }
            unset($clean['category_id']);
            break;

        case 'adddoc':

            // Security check
            if ($this->nb->isCategoryTrainer($clean['category_id'], $_SESSION['users_id'])) {
                $this->nb->trainDocument($clean['document'], $clean['category_id']);
            }

            // Scores have changed, clear bayes_cache            
            $vec_id = $this->nb->getVectorByCategory($clean['category_id']);
            $vec_id = array_keys($vec_id); // Get the key
            $vec_id = array_shift($vec_id);
            $this->nb->unsetCache($vec_id);
            
            unset($clean['document']);
            break;

        case 'remdoc':

            // Security check
            if ($this->nb->isDocumentOwner($clean['document_id'], $_SESSION['users_id'])) {
                // Remove any links to this document in associated link tables
                foreach ($this->link->getLinkTables('bayes') as $tmp) {
                    $this->link->deleteLink($tmp, 'bayes_documents', $clean['document_id']);
                }
                
                // Scores have changed, clear bayes_cache            
                $vectors= $this->nb->getVectorsByDocument($clean['document_id']);
                foreach($vectors as $key => $val) {
                    $this->nb->unsetCache($key);   
                }
                
                // Remove document
                $this->nb->untrainDocument($clean['document_id']);
            }
            unset($clean['document_id']);
            break;

        case 'sharevec' :

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {
                if (!isset($clean['trainer'])) $clean['trainer'] = 0;
                if (!isset($clean['owner'])) $clean['owner'] = 0;
                $this->nb->shareVector($clean['users_id'], $clean['vector_id'], $clean['trainer'], $clean['owner']);
                // TODO, clear caches for $clean['users_id'] (we need to get their partition, too)
            }
            break;

        case 'unsharevec' :

            foreach ($clean['unshare'] as $val) {
                foreach ($val as $vectors_id => $users_id) {
                    $this->nb->unshareVector($users_id, $vectors_id);
                    // TODO, clear caches for $users_id (we need to get their partition, too)
                }
            }
            break;

        }

    }


    /**
    * for suxValidate, check for an invalid vector share
    * i.e. cannot share a vector with one's self and
    *
    * @return bool
    */
    function invalidShare($value, $empty, &$params, &$formvars) {

        if (empty($formvars['users_id'])) return false;
        if ($formvars['users_id'] == $_SESSION['users_id']) return false;

        return true;

    }


    /**
    * for suxValidate, check if a user exists
    * i.e. cannot share a vector with one's self and
    *
    * @return bool
    */
    function userExists($value, $empty, &$params, &$formvars) {

        if (empty($formvars['users_id'])) return false;
        if (!$this->user->getUser($formvars['users_id'])) return false;

        return true;

    }


}


?>