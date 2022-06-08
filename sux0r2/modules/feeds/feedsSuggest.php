<?php

/**
* feedsSuggest
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class feedsSuggest extends component {

    // Module name
    protected $module = 'feeds';

    // Form name
    protected $form_name = 'feedsSuggest';

    // Object: suxRss()
    protected $rss;


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->rss = new suxRSS();
        $this->r = new suxRenderer($this->module); // Renderer
        (new suxValidate())->register_object('this', $this); // Register self to validator
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
        else (new suxValidate())->disconnect();

        if (!(new suxValidate())->is_registered_form()) {

            (new suxValidate())->connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            (new suxValidate())->register_criteria('isDuplicateFeed', 'this->isDuplicateFeed');
            (new suxValidate())->register_criteria('isValidFeed', 'this->isValidFeed');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            (new suxValidate())->register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            (new suxValidate())->register_validator('url2', 'url', 'isURL');
            (new suxValidate())->register_validator('url3', 'url', 'isDuplicateFeed');
            (new suxValidate())->register_validator('url4', 'url', 'isValidFeed');

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/feeds/suggest');
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

        $rss = [];
        $feed = $this->rss->fetchRss($clean['url']);

        $rss['url'] = $clean['url'];
        $rss['title'] = $feed['title'] ?? '---';
        $rss['body'] = $feed['description'] ?? '';
        $rss['draft'] = true;

        $id = $this->rss->saveFeed($_SESSION['users_id'], $rss);

        $this->log->write($_SESSION['users_id'], "sux0r::feedsSuggest() feeds_id: {$id}", 1); // Private

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
    function isDuplicateFeed($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;
        if ($this->rss->getFeedByID($formvars['url'])) return false;
        return true;

    }


    /**
    * for suxValidate, check if a RSS feed is valid
    *
    * @return bool
    */
    function isValidFeed($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;
        $feed = $this->rss->fetchRSS($formvars['url']);
        if (!isset($feed['items_count']) || $feed['items_count'] < 1) return false;
        return true;

    }


}


