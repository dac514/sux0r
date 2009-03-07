<?php

/**
* feedsAdmin
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once('feedsRenderer.php');


class feedsAdmin {

    // Variables
    public $per_page = 50;
    private $module = 'feeds';

    // Objects
    public $r;
    public $tpl;
    private $user;
    private $rss;
    private $pager;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator
        $this->user = new suxUser();
        $this->pager = new suxPager();
        $this->rss = new suxRSS();

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) {
            $access = $this->user->getAccess($this->module);
            if ($access < $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin'])
                suxFunct::redirect(suxFunct::makeUrl('/home'));
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


    function formBuild() {

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            suxValidate::register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');

        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        $this->tpl->assign('nickname', $_SESSION['nickname']);
        $this->tpl->assign('users_id', $_SESSION['users_id']);
        $this->r->text['form_url'] = suxFunct::makeUrl("/{$this->module}/admin");

        // Pager
        $this->pager->limit = $this->per_page;
        $this->pager->setStart();

        $this->pager->setPages($this->rss->countFeeds(true));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/{$this->module}/admin"));
        $this->r->arr['feeds'] = $this->rss->getFeeds($this->pager->limit, $this->pager->start, false);

        // Additional variables
        foreach ($this->r->arr['feeds'] as $key => $val) {
            $u = $this->user->getUser($val['users_id']);
            $this->r->arr['feeds'][$key]['nickname'] = $u['nickname'];
            $this->r->arr['feeds'][$key]['feeds_count'] = $this->rss->countItems($val['id']);
        }

        $this->r->title .= " | {$this->r->gtext['feeds']} | {$this->r->gtext['admin']}";

        // Display
        $this->tpl->display('admin.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        if (isset($clean['delete'])) foreach($clean['delete'] as $id => $val) {
                $this->rss->deleteFeed($id);
                $this->user->log("sux0r::feedsAdmin() deleted feeds_id: {$id}", $_SESSION['users_id'], 1); // Private
        }

        // clear all caches, cheap and easy
        $this->tpl->clear_all_cache();

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::makeUrl("/{$this->module}/admin/"));
    }


}


?>