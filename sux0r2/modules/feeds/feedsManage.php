<?php

/**
* feedsManage
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('feedsRenderer.php');

class feedsManage  {

    // Variables
    public $gtext = array();
    private $module = 'feeds';

    // Objects
    public $tpl;
    public $r;
    protected $user;
    protected $rss;
    protected $link;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->rss = new suxRSS();
        $this->user = new suxUser(); // User
        $this->link = new suxLink();
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator

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
            $this->r->arr['subscriptions'] = $this->link->getLinks('link_rss_users', 'users', $_SESSION['users_id']);
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

        $this->link->deleteLink('link_rss_users', 'users', $_SESSION['users_id']);

        if (isset($clean['subscriptions']) && count($clean['subscriptions']))
            $this->link->saveLink('link_rss_users', 'users', $_SESSION['users_id'], 'rss_feeds', $clean['subscriptions']);

        $this->user->log("sux0r::feedManage()", $_SESSION['users_id'], 1); // Private

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // clear all caches with "nickname" as the first cache_id group
        $this->tpl->clear_cache(null, "{$_SESSION['nickname']}");

        // Redirect
        suxFunct::redirect(suxFunct::getPreviousURL());

    }



}


?>