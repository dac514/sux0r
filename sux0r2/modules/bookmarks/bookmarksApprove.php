<?php

/**
* bookmarksApprove
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class bookmarksApprove extends component {

    // Module name
    protected $module = 'bookmarks';

    // Object: suxBookmarks()
    protected $bm;

    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->bm = new suxBookmarks();
        $this->r = new bookmarksRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->bm->setPublished(false);

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin'])
                suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));
        }

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

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('bookmarks', 'bookmarks', 'isInt', true);

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks/approve');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        // bookmarks
        $this->r->arr['bookmarks'] = $this->bm->get();

        // Adjust variables
        foreach ($this->r->arr['bookmarks'] as $key => $val) {
            if (!$val['draft']) {
                // This bookmark is not a draft, it's just in the future, ignore it.
                unset($this->r->arr['bookmarks'][$key]);
                continue;
            }
            // Append nickname
            $u = $this->user->getByID($val['users_id']);
            $this->r->arr['bookmarks'][$key]['nickname'] = $u['nickname'];
        }


        $this->r->title .= " | {$this->r->gtext['approve']}";

        $this->tpl->display('approve.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        if (isset($clean['bookmarks'])) foreach ($clean['bookmarks'] as $key => $val) {

            if ($val == 1) {
                $this->bm->draft($key, false);
                $this->log->write($_SESSION['users_id'], "sux0r::bookmarksApprove() bookmarks_id: {$key}", 1); // Private
            }
            else {
                $this->bm->delete($key);
                $this->log->write($_SESSION['users_id'], "sux0r::bookmarksApprove() deleted bookmarks_id: {$key}", 1); // Private
            }

        }

        // clear all caches, cheap and easy
        $this->tpl->clear_all_cache();

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Redirect
        suxFunct::redirect(suxFunct::getPreviousURL());

    }



}


?>