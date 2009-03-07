<?php

/**
* feedsApprove
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('feedsRenderer.php');

class feedsApprove  {

    // Variables
    public $gtext = array();
    private $module = 'feeds';

    // Objects
    public $tpl;
    public $r;
    protected $user;
    protected $rss;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->rss = new suxRSS();
        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator

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
        $this->r->arr['feeds'] = $this->rss->getUnpublishedFeeds();

        // Additional variables
        foreach ($this->r->arr['feeds'] as $key => $val) {
            $u = $this->user->getUser($val['users_id']);
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
                $this->user->log("sux0r::feedsApprove() feeds_id: {$key}", $_SESSION['users_id'], 1); // Private
            }
            else {
                $this->rss->deleteFeed($key);
                $this->user->log("sux0r::feedsApprove() deleted feeds_id: {$key}", $_SESSION['users_id'], 1); // Private
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