<?php

/**
* feedsApprove
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class feedsApprove extends component  {

    // Module name
    protected $module = 'feeds';

    // Object: suxRss()
    protected $rss;


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->rss = new suxRSS();
        $this->r = new feedsRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Declare Properties
        $this->rss->setPublished(false);

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin'])
                suxFunct::redirect(suxFunct::makeUrl('/feeds'));
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

            suxValidate::register_validator('feeds', 'feeds', 'isInt', true);

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/feeds/approve');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        // Feeds
        $this->r->arr['feeds'] = $this->rss->getFeeds();

        // Additional variables
        foreach ($this->r->arr['feeds'] as $key => $val) {
            $u = $this->user->getByID($val['users_id']);
            $this->r->arr['feeds'][$key]['nickname'] = $u['nickname'];
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

        if (isset($clean['feeds'])) foreach ($clean['feeds'] as $key => $val) {

            if ($val == 1) {
                $this->rss->approveFeed($key);
                $this->log->write($_SESSION['users_id'], "sux0r::feedsApprove() feeds_id: {$key}", 1); // Private
            }
            else {
                $this->rss->deleteFeed($key);
                $this->log->write($_SESSION['users_id'], "sux0r::feedsApprove() deleted feeds_id: {$key}", 1); // Private
            }

        }

        // clear all caches,cheap and easy
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