<?php

/**
* blog
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('blogRenderer.php');


class blog  {

    // Variables
    public $gtext = array();
    private $module = 'blog';

    // Objects
    private $liuk;
    private $msg;
    private $nb;
    private $pager;
    private $user;
    public $r;
    public $tpl;


    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new blogRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->msg = new suxThreadedMessages();
        $this->link = new suxLink();
        $this->nb = new bayesUser();

        $this->pager = new suxPager();
        $this->pager->limit = 2;

    }


    /**
    * Author
    */
    function author($author) {

        $u = $this->user->getUserByNickname($author);
        if ($u) {

            // Pager
            $this->pager->setStart();
            $this->pager->setPages($this->msg->countFirstPostsByUser($u['users_id'], 'blog'));
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/author/' . $author));

            // Assign
            $this->r->fp = $this->blogs($this->msg->getFirstPostsByUser($u['users_id'], 'blog', true, $this->pager->limit, $this->pager->start));
            $this->r->sidelist = $this->msg->getFirstPostsByUser($u['users_id'], 'blog'); // TODO: Too many blogs?
            $this->r->text['sidelist'] = ucwords($author);

        }


        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


    /**
    * Category
    */
    function category($cat_id) {

        $c = $this->nb->getCategory($cat_id);
        if ($c) {

            // ----------------------------------------------------------------
            // SQL
            // ----------------------------------------------------------------

            $db = suxDB::get();
            $db_driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

            // Innerjoin query
            $innerjoin = '
            INNER JOIN link_bayes_messages ON link_bayes_messages.messages_id = messages.id
            INNER JOIN bayes_documents ON link_bayes_messages.bayes_documents_id = bayes_documents.id
            INNER JOIN bayes_categories ON bayes_documents.bayes_categories_id = bayes_categories.id
            ';

            // Date query, database specic
            if ($db_driver == 'mysql') {
                $date = 'AND NOT published_on > \'' . date('Y-m-d H:i:s') . '\' ';
            }
            else {
                throw new Exception('Unsupported database driver');
            }

            // Count
            $count_query = "
            SELECT COUNT(*) FROM messages
            {$innerjoin}
            WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND bayes_categories.id = ?
            {$date}
            ";
            $st = $db->prepare($count_query);
            $st->execute(array($cat_id));
            $count = $st->fetchColumn();

            if ($count) {

                // Select, with limits
                $limit_query = "
                SELECT messages.*, LENGTH(messages.body_plaintext) AS body_length FROM messages
                {$innerjoin}
                WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND bayes_categories.id = ?
                {$date}
                ORDER BY messages.published_on DESC
                ";
                if ($this->pager->start && $this->pager->limit) $limit_query .= "LIMIT {$this->pager->start}, {$this->pager->limit} ";
                elseif ($this->pager->limit) $limit_query .= "LIMIT {$this->pager->limit} ";

                $st = $db->prepare($limit_query);
                $st->execute(array($cat_id));
                $fp = $st->fetchAll(PDO::FETCH_ASSOC);

                // Select, for sidebar
                $select_query = "
                SELECT messages.id, messages.thread_id, messages.title FROM messages
                {$innerjoin}
                WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND bayes_categories.id = ?
                {$date}
                ORDER BY messages.published_on DESC
                ";

                $st = $db->prepare($select_query);
                $st->execute(array($cat_id));
                $sidelist = $st->fetchAll(PDO::FETCH_ASSOC);

                // ----------------------------------------------------------------
                // Template
                // ----------------------------------------------------------------

                // Pager
                $this->pager->setStart();
                $this->pager->setPages($count);
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/category/' . $cat_id));

                // Assign
                $this->r->fp = $this->blogs($fp);
                $this->r->sidelist = $sidelist;
                $this->r->text['sidelist'] = $c['category'];

            }

        }

        $this->tpl->assign('category_id', $cat_id);
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


    /**
    * Month
    */
    function month($date) {

        // Sanity check, YYYY-MM-DD
        $matches = array();
        $regex = '/^(\d{4})-(0[0-9]|1[0,1,2])-([0,1,2][0-9]|3[0,1])$/';
        if (!preg_match($regex, $date)) $date = date('Y-m-d');
        $datetime = $date . ' ' . date('H:i:s'); // Append current time

        // Pager
        $this->pager->setStart();
        $this->pager->setPages($this->msg->countFirstPostsByMonth($datetime, 'blog'));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/month/' . $date));

        // Assign
        $this->r->fp = $this->blogs($this->msg->getFirstPostsByMonth($datetime, 'blog', true, $this->pager->limit, $this->pager->start));
        $this->r->sidelist = $this->msg->getFirstPostsByMonth($datetime, 'blog');
        $this->r->text['sidelist'] = date('F Y', strtotime($date));

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


    /**
    * Filter
    */
    function filter($cat_id, $threshold) {

        $threshold = filter_var($threshold, FILTER_VALIDATE_FLOAT);
        if ($threshold >= 1 ) suxFunct::redirect(suxFunct::makeUrl('/blog/category/' . $cat_id));

        $this->user->loginCheck(suxFunct::makeUrl('/blog')); // Skip anonymous users
        $vec_id = key($this->nb->getVectorByCategory($cat_id));
        if (!$vec_id) suxFunct::redirect(suxFunct::makeUrl('/blog'));
        if (!$this->nb->isVectorOwner($vec_id, $_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/blog'));

        // TODO, some sort of pager mechanism is needed

        $fp = $this->blogs($this->msg->getFirstPosts('blog', true));
        if ($threshold > 0 && $threshold < 1) {
            foreach ($fp as $key => $val) {
                $score = $this->nb->categorize($val['body_plaintext'], $vec_id);
                if ($score[$cat_id]['score'] < $threshold) {
                    unset($fp[$key]);
                    continue;
                }
            }
        }

        $this->r->fp = $fp;
        $this->tpl->assign('category_id', $cat_id);
        $this->tpl->assign('threshold', $threshold);

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


    /**
    * Listing
    */
    function listing() {

        // Pager
        $this->pager->setStart();
        $this->pager->setPages($this->msg->countFirstPosts('blog'));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog'));

        // Assign
        $this->r->fp = $this->blogs($this->msg->getFirstPosts('blog', true, $this->pager->limit, $this->pager->start));

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


    /**
    * View
    */
    function view($thread_id) {

        $this->pager->limit = 100;

        // Pager
        $this->pager->setStart();
        $this->pager->setPages($this->msg->countThread($thread_id, 'blog'));
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/view/' . $thread_id));

        if ($this->pager->start == 0) {
            $thread = $this->msg->getThread($thread_id, 'blog', true, $this->pager->limit, $this->pager->start);
            $fp[] = array_shift($thread);
        }
        else {
            $thread = $this->msg->getThread($thread_id, 'blog', true, $this->pager->limit, $this->pager->start);
            $fp[] = $this->msg->getFirstPost($thread_id, 'blog');
        }

        // Assign
        $this->r->fp = $this->blogs($fp);
        $this->r->comments = $this->comments($thread);

        // $this->r->sidelist = $this->msg->getFirstPostsByUser($u['users_id'], 'blog'); // TODO: Too many blogs?
        // $this->r->text['sidelist'] = ucwords($author);


        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('view.tpl');

    }



    /**
    * @param array threaded messages
    * @return array
    */
    private function blogs($msgs) {

        foreach($msgs as &$val) {

            $val['comments'] = $this->msg->getCommentsCount($val['thread_id']);
            $user = $this->user->getUser($val['users_id']);
            $val['nickname'] = $user['nickname'];

        }

        return $msgs;

    }


    /**
    * @param array threaded messages
    * @return array
    */
    private function comments($msgs) {

        foreach($msgs as &$val) {

            $user = $this->user->getUser($val['users_id']);
            $val['nickname'] = $user['nickname'];

        }

        return $msgs;

    }


}


?>