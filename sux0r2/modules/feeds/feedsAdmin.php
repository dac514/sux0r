<?php

/**
* feedsAdmin
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class feedsAdmin extends component {

    // Module name
    protected $module = 'feeds';

    // Object: suxRSS()
    protected $rss;

    // Var: for pager
    public $per_page = 50;


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
        $this->rss->setPublished(null);

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


    function formBuild(&$dirty) {

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

        $this->pager->setPages($this->rss->countFeeds());
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/{$this->module}/admin"));
        $this->r->arr['feeds'] = $this->rss->getFeeds($this->pager->limit, $this->pager->start);

        // Additional variables
        foreach ($this->r->arr['feeds'] as $key => $val) {
            $u = $this->user->getByID($val['users_id']);
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
                $this->log->write($_SESSION['users_id'], "sux0r::feedsAdmin() deleted feeds_id: {$id}", 1); // Private
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