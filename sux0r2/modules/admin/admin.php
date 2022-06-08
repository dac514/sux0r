<?php

/**
* admin
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class admin extends component {

    // Module name
    protected $module = 'admin';

    // Var: pager value
    public $per_page = 100;


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->r = new adminRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) suxFunct::redirect(suxFunct::makeUrl('/home'));

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

        // -------------------------------------------------------------------
        // Sort / Order
        // -------------------------------------------------------------------

        $sort = '';
        if (isset($_GET['sort'])) $sort = $_GET['sort'];

        $order = 'DESC';
        if (!empty($_GET['order'])) $order = $_GET['order'];


        // -------------------------------------------------------------------
        // Pager
        // -------------------------------------------------------------------

        $params = array(); // Extra params
        if ($sort) $params = array('sort' => $sort, 'order' => $order);

        $this->pager->limit = $this->per_page;
        $this->pager->setStart();
        $this->pager->setPages($this->user->count());
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/admin', $params));

        // -------------------------------------------------------------------
        // Template
        // -------------------------------------------------------------------

        $this->tpl->assign('nickname', $_SESSION['nickname']);
        $this->tpl->assign('users_id', $_SESSION['users_id']);
        $this->r->text['form_url'] = suxFunct::makeUrl("/{$this->module}/admin");


        $valid = array('users_id', 'nickname', 'email', 'root', 'banned', 'ts');
        if (in_array(mb_strtolower($sort), $valid)) {
            if ($sort == 'ts') $this->user->setOrder('last_active', $order);
            else $this->user->setOrder($sort, $order);
        }

        $this->r->arr['ulist'] = $this->user->get($this->pager->limit, $this->pager->start);

        // Additional variables
        foreach ($this->r->arr['ulist'] as $key => $val) {
            $u = $this->user->getByID($val['users_id'], true);
            $this->r->arr['ulist'][$key]['url'] = $u['url'];
        }

        $this->tpl->assign('sort', $sort);
        $this->r->text['sort_url'] = suxFunct::makeUrl('/admin', array('order' => (mb_strtolower($order) == 'desc' ? 'ASC' : 'DESC')));

        $this->r->title .= " | {$this->r->gtext['admin']}";

        $this->tpl->display('userlist.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {


        if (isset($clean['delete'])) foreach($clean['delete'] as $id => $val) {

            // Begin transaction
            $db = suxDB::get();
            $tid = suxDB::requestTransaction();

            try {

                $query = 'DELETE FROM bayes_auth WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM bookmarks WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM link__bookmarks__users WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM link__rss_feeds__users WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM messages WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM messages_history WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM openid_trusted WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM photoalbums WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM photos WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM rss_feeds WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM socialnetwork WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM socialnetwork WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM tags WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM users_access WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM users_info WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM users_log WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM users_openid WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                $query = 'DELETE FROM users WHERE id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($id));

                // Log, private
                $this->log->write($_SESSION['users_id'], "sux0r::adminAccess() deleted users_id: {$id} ", 1);

            }
            catch (Exception $e) {

                $db->rollback();
                throw($e); // Hot potato!
            }

            suxDB::commitTransaction($tid); // Commit

            // clear all caches,cheap and easy
            $this->tpl->clearAllCache();
        }
    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::makeUrl("/{$this->module}/admin/"));
    }


}


