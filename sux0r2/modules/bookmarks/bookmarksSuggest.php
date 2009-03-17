<?php

/**
* bookmarksSuggest
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxBookmarks.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class bookmarksSuggest  {

    // Variables
    public $gtext = array();
    private $module = 'bookmarks';

    // Objects
    public $tpl;
    public $r;
    protected $user;
    protected $bm;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->bm = new suxBookmarks();
        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new suxRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator

        // Object properties
        $this->bm->setPublished(null);

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
        return suxValidate::formValidate($dirty, $this->tpl);
    }


    /**
    * Build the form and show the template
    *
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild(&$dirty) {

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            suxValidate::register_criteria('isDuplicateBookmark', 'this->isDuplicateBookmark');
            suxValidate::register_criteria('isValidBookmark', 'this->isValidBookmark');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');
            suxValidate::register_validator('url3', 'url', 'isDuplicateBookmark');
            suxValidate::register_validator('url4', 'url', 'isValidBookmark');

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks/suggest');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        $this->r->title .= " | {$this->r->gtext['suggest']}";

        // Template
        $this->tpl->display('suggest.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $bm = $this->bm->fetchUrlInfo($clean['url']);

        $bookmark['url'] = $clean['url'];
        $bookmark['title'] = isset($bm['title']) ? $bm['title'] : '---';
        $bookmark['body'] = isset($bm['description']) ? $bm['description'] : '';
        $bookmark['draft'] = true;

        $id = $this->bm->save($_SESSION['users_id'], $bookmark);

        $this->user->log("sux0r::bookmarksSuggest() bookmarks_id: {$id}", $_SESSION['users_id'], 1); // Private

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Template
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->gtext['success']}";

        $this->tpl->display('success.tpl');

    }



    /**
    * for suxValidate, check if a duplicate url exists
    *
    * @return bool
    */
    function isDuplicateBookmark($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;
        if ($this->bm->getByID($formvars['url'])) return false;
        return true;

    }


    /**
    * for suxValidate, check if a bookmark is valid
    *
    * @return bool
    */
    function isValidBookmark($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;
        $bm = $this->bm->fetchUrlInfo($formvars['url']);
        if (!$bm) return false;
        return true;

    }


}


?>