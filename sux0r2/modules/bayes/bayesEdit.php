<?php

/**
* bayesEdit
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once('bayesRenderer.php');
require_once(dirname(__FILE__) . '/../../extensions/suxUserNaiveBayesian.php');
require_once(dirname(__FILE__) . '/../abstract.component.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');


class bayesEdit extends component {

    // Module name
    protected $module = 'bayes';

    // Object: suxUserNaiveBayesian();
    public $nb;

    // Array: Modules that cache bayes interfaces
    public $caches = array('blog', 'feeds', 'bookmarks');


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->nb = new suxUserNaiveBayesian();
        $this->r = new bayesRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest


        // If feature is turned off, then redirect
        if ($GLOBALS['CONFIG']['FEATURE']['bayes'] == false) suxFunct::redirect(suxFunct::getPreviousURL());

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {

        if (empty($dirty['action'])) return false;

        $action = filter_var($dirty['action'], FILTER_SANITIZE_STRING);
        return suxValidate::formValidate($dirty, $this->tpl, $action);

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

        $this->r->title .= " | {$this->r->gtext['edit_bayes']}";

        // Template
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
            $this->log->write($_SESSION['users_id'], "sux0r::bayesEdit() addvec", 1); // Private
            break;

        case 'remvec':

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {
                // Remove vector
                $this->nb->removeVector($clean['vector_id']);
                $this->log->write($_SESSION['users_id'], "sux0r::bayesEdit() remvec id: {$clean['vector_id']}", 1); // Private
            }
            unset($clean['vector_id']);
            break;

        case 'addcat':

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {
                $this->nb->addCategory($clean['category'], $clean['vector_id']);
                $this->log->write($_SESSION['users_id'], "sux0r::bayesEdit() addcat", 1); // Private
            }
            unset($clean['category']);
            break;

        case 'remcat':

            // Security check
            if ($this->nb->isCategoryOwner($clean['category_id'], $_SESSION['users_id'])) {
                // Remove category
                $this->nb->removeCategory($clean['category_id']);
                $this->log->write($_SESSION['users_id'], "sux0r::bayesEdit() remcat id: {$clean['category_id']}", 1); // Private
            }
            unset($clean['category_id']);
            break;

        case 'adddoc':

            // Security check
            if ($this->nb->isCategoryTrainer($clean['category_id'], $_SESSION['users_id'])) {
                $this->nb->trainDocument($clean['document'], $clean['category_id']);
                $this->log->write($_SESSION['users_id'], "sux0r::bayesEdit() adddoc", 1); // Private
            }
            unset($clean['document']);
            break;

        case 'remdoc':

            // Security check
            if ($this->nb->isDocumentOwner($clean['document_id'], $_SESSION['users_id'])) {
                // Remove document
                $this->nb->untrainDocument($clean['document_id']);
                $this->log->write($_SESSION['users_id'], "sux0r::bayesEdit() remdoc id: {$clean['document_id']}", 1); // Private
            }
            unset($clean['document_id']);
            break;

        case 'sharevec' :

            // Security check
            if ($this->nb->isVectorOwner($clean['vector_id'], $_SESSION['users_id'])) {

                $clean['trainer'] = (isset($clean['trainer']) && $clean['trainer']) ? true : false;
                $clean['owner'] = (isset($clean['owner']) && $clean['owner']) ? true : false;
                $this->nb->shareVector($clean['users_id'], $clean['vector_id'], $clean['trainer'], $clean['owner']);

                $u = $this->user->getByID($clean['users_id']);

                // clear caches
                foreach ($this->caches as $module) {
                    $tpl = new suxTemplate($module);
                    $tpl->clear_cache(null, $_SESSION['nickname']);
                    $tpl->clear_cache(null, $u['nickname']);
                }

                // Log message
                $log = '';
                $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
                $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
                $log .= mb_strtolower($this->r->gtext['share_category']);
                $url = suxFunct::makeUrl("/user/profile/{$u['nickname']}", null, true);
                $log .= " <a href='$url'>{$u['nickname']}</a>";

                // Log
                $this->log->write($_SESSION['users_id'], $log);
                $this->log->write($u['users_id'], $log);

                // Clear caches
                $tpl = new suxTemplate('user');
                $tpl->clear_cache(null, $_SESSION['nickname']);
                $tpl->clear_cache(null, $u['nickname']);

            }
            break;

        case 'unsharevec' :

            foreach ($clean['unshare'] as $val) {
                foreach ($val as $vectors_id => $users_id) {
                    $this->nb->unshareVector($users_id, $vectors_id);

                    $u = $this->user->getByID($users_id);

                    // Clear caches
                    foreach ($this->caches as $module) {
                        $tpl = new suxTemplate($module);
                        $tpl->clear_cache(null, $_SESSION['nickname']);
                        $tpl->clear_cache(null, $u['nickname']);
                    }

                    // Log message
                    $log = '';
                    $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
                    $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
                    $log .= mb_strtolower($this->r->gtext['unshare_category']);
                    $url = suxFunct::makeUrl("/user/profile/{$u['nickname']}", null, true);
                    $log .= " <a href='$url'>{$u['nickname']}</a>";

                    // Log
                    $this->log->write($_SESSION['users_id'], $log);
                    $this->log->write($u['users_id'], $log);

                    // Clear caches
                    $tpl = new suxTemplate('user');
                    $tpl->clear_cache(null, $_SESSION['nickname']);
                    $tpl->clear_cache(null, $u['nickname']);

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
        if (!$this->user->getByID($formvars['users_id'])) return false;

        return true;

    }


}


?>