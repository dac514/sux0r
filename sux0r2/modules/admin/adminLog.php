<?php

/**
* adminLog
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class adminLog extends component {

    // Module name
    protected $module = 'admin';

    // Var:
    private $users_id;

    // Var:
    private $nickname;

    // Var:
    public $per_page = 100;


    /**
    * Constructor
    *
    * @param string nickname
    */
    function __construct($nickname = null) {

        // Declare objects
        $this->r = new adminRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) suxFunct::redirect(suxFunct::makeUrl('/home'));

        // Declare properties
        $this->log->setPublished(null);

        $tmp = $this->user->getByNickname($nickname);

        if ($tmp) {
            $this->users_id = $tmp['users_id'];
            $this->nickname = $tmp['nickname'];
        }

    }


    function display() {

        $order = 'desc';
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
            $this->tpl->assign('sort', 'ts');
        }

        // Extra params for pager
        $params = array();
        if ($order) $params = array('order' => $order);

        // Pager
        $this->pager->limit = $this->per_page;
        $this->pager->setStart();

        $this->pager->setPages($this->log->count($this->users_id));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/admin/log/{$this->nickname}", $params));

        $this->log->setOrder('ts', $order);
        $this->r->arr['ulog'] = $this->log->get($this->pager->limit, $this->pager->start, $this->users_id);
        foreach ($this->r->arr['ulog'] as $key => $val) {
            $tmp = $this->user->getByID($val['users_id']);
            $this->r->arr['ulog'][$key]['nickname'] = $tmp['nickname'];
        }

        // Template
        $inverse = ($order != 'desc') ? 'desc' : 'asc';
        $this->tpl->assign('ts_sort_url', suxFunct::makeUrl("/admin/log/{$this->nickname}", array('order' => $inverse)));
        $this->tpl->assign('nickname', $this->nickname);

        $this->r->title .= " | {$this->r->gtext['activity_log']}";

        $this->tpl->display('log.tpl');

    }


}


