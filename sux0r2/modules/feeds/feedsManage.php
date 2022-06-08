<?php

/**
* feedsManage
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class feedsManage extends component  {

    // Module name
    protected $module = 'feeds';

    // Form name
    protected $form_name = 'feedsManage';

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

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('subscriptions', 'subscriptions', 'isInt', true);

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/feeds/manage');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        // Feeds
        $feeds = array();
        foreach ($this->rss->getFeeds() as $feed) {
            $feeds[$feed['id']] = $feed['title'];
        }
        $this->r->arr['feeds'] = $feeds;

        // Subscriptions
        if (!isset($_POST['subscriptions'])) {
            $this->r->arr['subscriptions'] = $this->link->getLinks('link__rss_feeds__users', 'users', $_SESSION['users_id']);
        }

        $this->r->title .= " | {$this->r->gtext['manage']}";

        $this->tpl->display('manage.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $this->link->deleteLink('link__rss_feeds__users', 'users', $_SESSION['users_id']);

        if (isset($clean['subscriptions']) && count($clean['subscriptions']))
            $this->link->saveLink('link__rss_feeds__users', 'users', $_SESSION['users_id'], 'rss_feeds', $clean['subscriptions']);

        $this->log->write($_SESSION['users_id'], "sux0r::feedManage()",  1); // Private

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // clear all caches with "nickname" as the first cache_id group
        $this->tpl->clearCache(null, "{$_SESSION['nickname']}");

        // Redirect
        suxFunct::redirect(suxFunct::getPreviousURL());

    }



}


