<?php

/**
* adminLog
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('adminRenderer.php');


class adminLog {

    // Variables
    private $users_id;
    private $nickname;
    public $per_page = 100;
    private $module = 'admin';

    // Objects
    public $r;
    public $tpl;
    protected $user;
    private $pager;


    /**
    * Constructor
    *
    * @param string nickname
    */
    function __construct($nickname = null) {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new adminRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->user = new suxUser();
        $this->pager = new suxPager();

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) suxFunct::redirect(suxFunct::makeUrl('/home'));

        $tmp = $this->user->getUserByNickname($nickname);

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

        $this->pager->setPages($this->user->countLog($this->users_id));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/admin/log/{$this->nickname}", $params));
        $this->r->arr['ulog'] = $this->user->getLog($this->pager->limit, $this->pager->start, $this->users_id, $order, true);

        // Template
        $inverse = ($order != 'desc') ? 'desc' : 'asc';
        $this->tpl->assign('ts_sort_url', suxFunct::makeUrl("/admin/log/{$this->nickname}", array('order' => $inverse)));
        $this->tpl->assign('nickname', $this->nickname);

        $this->r->title .= " | {$this->r->gtext['activity_log']}";

        $this->tpl->display('log.tpl');

    }


}


?>