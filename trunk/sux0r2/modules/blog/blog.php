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
require_once(dirname(__FILE__) . '/../../includes/suxTags.php');
require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php'); // includes bayesShared
require_once('blogRenderer.php');


class blog extends bayesShared {

    // Variables
    public $tag_id; // For filter
    public $cat_id; // For filter
    public $gtext = array();
    private $module = 'blog';

    // Objects
    public $r;
    public $tpl;

    protected $msg;
    protected $nb;
    protected $pager;

    private $liuk;
    private $user;
    private $tags;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new blogRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template

        $this->r->bool['analytics'] = true; // Turn on analytics

        $this->user = new suxUser();
        $this->msg = new suxThreadedMessages();
        $this->link = new suxLink();
        $this->nb = new bayesUser();
        $this->tags = new suxTags();
        $this->pager = new suxPager();

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);


    }


    /**
    * Author
    */
    function author($author) {

        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/author/' . $author); // Form Url
        $cache_id = false;

        $u = $this->user->getUserByNickname($author);
        if(!$u) suxFunct::redirect(suxFunct::makeUrl('/blog'));

        $this->r->title .= " | {$this->r->gtext['blog']} | $author";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->msg->countFirstPostsByUser($u['users_id'], 'blog');
            $eval = '$this->msg->getFirstPostsByUser(' .$u['users_id'] . ', \'blog\', $this->pager->limit, $start)';
            $this->r->arr['fp']  = $this->blogs($this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search)); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl('/blog/author/'. $author, $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|author|' . $author . '|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->msg->countFirstPostsByUser($u['users_id'], 'blog'));
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/author/' . $author));
                $this->r->arr['fp'] = $this->blogs($this->msg->getFirstPostsByUser($u['users_id'], 'blog', $this->pager->limit, $this->pager->start));

                if (!count($this->r->arr['fp'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        // ---------------------------------------------------------------
        // Sidelist
        // ---------------------------------------------------------------

        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

            $this->r->arr['sidelist'] = $this->msg->getFirstPostsByUser($u['users_id'], 'blog'); // TODO: Too many blogs?
            $this->r->text['sidelist'] = ucwords($author);

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    /**
    * Tag
    */
    function tag($tag_id) {

        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/tag/' . $tag_id); // Form Url
        $cache_id = false;

        $tag = $this->tags->getTag($tag_id);
        if (!$tag) suxFunct::redirect(suxFunct::makeUrl('/blog'));
        $this->tag_id = $tag_id; // Needs to be in externally accessible variable for filter()

        $count = $this->countTaggedItems($this->tag_id);

        $this->r->title .= " | {$this->r->gtext['blog']} | {$this->r->gtext['tag']} | {$tag['tag']}";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $eval = '$this->getTaggedItems($this->tag_id, $this->pager->limit, $start)';
            $this->r->arr['fp']  = $this->blogs($this->filter($count, $vec_id, $cat_id, $threshold, $start, $eval, $search)); // Important: $start is a reference

            if ($start < $count) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl('/blog/tag/'. $this->tag_id, $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|tag|' . $this->tag_id . '|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($count);
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/tag/' . $this->tag_id));
                $this->r->arr['fp'] = $this->blogs($this->getTaggedItems($this->tag_id, $this->pager->limit, $this->pager->start));

                if (!count($this->r->arr['fp'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        // ---------------------------------------------------------------
        // Sidelist
        // ---------------------------------------------------------------

        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

            $this->r->arr['sidelist'] = $this->getTaggedSidelist($this->tag_id);
            $this->r->text['sidelist'] = $tag['tag'];

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    /**
    * Tag cloud
    */
    function tagcloud() {

        // ---------------------------------------------------------------
        // Tagcloud, cached
        // ---------------------------------------------------------------

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        $cache_id = "$nn|tagcloud";
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('cloud.tpl', $cache_id)) {

            $link = $this->link->getLinkTableName('messages', 'tags');
            $query = "
            SELECT tags.tag AS tag, tags.id AS id, COUNT(tags.id) AS quantity FROM tags
            INNER JOIN {$link} ON {$link}.tags_id = tags.id
            INNER JOIN messages ON {$link}.messages_id = messages.id
            WHERE messages.blog = true AND messages.draft = false {$this->_dateSql()}
            GROUP BY tag, tags.id ORDER BY tag ASC
            ";
            $this->r->arr['tc'] = $this->tags->tagcloud($query);

            $this->r->title .= " | {$this->r->gtext['blog']} | {$this->r->gtext['tag_cloud']} ";

        }

        $this->tpl->display('cloud.tpl', $cache_id);

    }


    /**
    * Category
    */
    function category($cat_id) {

        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/category/' . $cat_id); // Form Url
        $cache_id = false;

        $c = $this->nb->getCategory($cat_id);
        if (!$c) suxFunct::redirect(suxFunct::makeUrl('/blog'));
        $this->cat_id = $cat_id; // Needs to be in externally accessible variable for filter()

        $count = $this->countCategorizedItems($this->cat_id);

        $this->r->title .= " | {$this->r->gtext['blog']}  | {$this->r->gtext['category']} | {$c['category']}";

        if (list($vec_id, $cat_id2, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $eval = '$this->getCategorizedItems($this->cat_id, $this->pager->limit, $start)';
            $this->r->arr['fp']  = $this->blogs($this->filter($count, $vec_id, $cat_id2, $threshold, $start, $eval, $search)); // Important: $start is a reference

            if ($start < $count) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id2);
                else $params = array('filter' => $cat_id2);
                $params['search'] = $search;
                $url = suxFunct::makeUrl('/blog/category/'. $this->cat_id, $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }



        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|category|' . $this->cat_id . '|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($count);
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/category/' . $this->cat_id));
                $this->r->arr['fp'] = $this->blogs($this->getCategorizedItems($this->cat_id, $this->pager->limit, $this->pager->start));

                if (!count($this->r->arr['fp'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }
        }

        // ---------------------------------------------------------------
        // Sidelist
        // ---------------------------------------------------------------

        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

            $this->r->arr['sidelist'] = $this->getCategorizedSidelist($this->cat_id);
            $this->r->text['sidelist'] = $c['category'];

        }


        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

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

        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/month/' . $date); // Form Url
        $cache_id = false;

        $this->r->title .= " | {$this->r->gtext['blog']}  | " .  date('F Y', strtotime($date));

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->msg->countFirstPostsByMonth($datetime, 'blog');
            $eval = '$this->msg->getFirstPostsByMonth(\'' . $datetime . '\', \'blog\', $this->pager->limit, $start)';
            $this->r->arr['fp']  = $this->blogs($this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search)); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl('/blog/month/'. $date, $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|month|' . date('Y-m', strtotime($date)) . '|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->msg->countFirstPostsByMonth($datetime, 'blog'));
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/month/' . $date));
                $this->r->arr['fp'] = $this->blogs($this->msg->getFirstPostsByMonth($datetime, 'blog', $this->pager->limit, $this->pager->start));

                if (!count($this->r->arr['fp'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        // ---------------------------------------------------------------
        // Sidelist
        // ---------------------------------------------------------------

        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

            $this->r->arr['sidelist'] = $this->msg->getFirstPostsByMonth($datetime, 'blog');
            $this->r->text['sidelist'] = date('F Y', strtotime($date));

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    /**
    * Listing
    */
    function listing() {

        $this->r->text['form_url'] = suxFunct::makeUrl('/blog'); // Form Url
        $cache_id = false;

        $this->r->title .= " | {$this->r->gtext['blog']}";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->msg->countFirstPosts('blog');
            $eval = '$this->msg->getFirstPosts(\'blog\', $this->pager->limit, $start)';
            $this->r->arr['fp']  = $this->blogs($this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search)); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl('/blog/', $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|listing|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->msg->countFirstPosts('blog'));
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog'));
                $this->r->arr['fp'] = $this->blogs($this->msg->getFirstPosts('blog', $this->pager->limit, $this->pager->start));

                if (!count($this->r->arr['fp'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');


    }


    /**
    * View
    */
    function view($thread_id) {

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        // Start pager
        $this->pager->limit = 100;
        $this->pager->setStart();

        // "Cache Groups" using a vertical bar |
        $cache_id = $nn . "|{$thread_id}|" . $this->pager->start;
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('view.tpl', $cache_id)) {

            $fp[] = $this->msg->getFirstPost($thread_id);

            if ($fp[0] === false) {
                // This is not a blog post, redirect
                suxFunct::redirect(suxFunct::makeUrl('/blog'));
            }

            $this->r->title .= " | {$this->r->gtext['blog']} | {$fp[0]['title']}";

            $this->pager->setPages($this->msg->countThread($thread_id, 'blog'));
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/view/' . $thread_id));

            if ($this->pager->start == 0) {
                $thread = $this->msg->getThread($thread_id, 'blog', $this->pager->limit, $this->pager->start);
                unset($fp);
                $fp[] = array_shift($thread);
            }
            else {
                $thread = $this->msg->getThread($thread_id, 'blog', $this->pager->limit, $this->pager->start);
            }

            // Assign
            $this->r->arr['fp'] = $this->blogs($fp);
            $this->r->arr['comments'] = $this->comments($thread);

        }

        $this->tpl->display('view.tpl', $cache_id);

    }


    /**
    * Display RSS Feed
    */
    function rss() {

        // Cache
        $cache_id = 'rss';
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('rss.tpl', $cache_id)) {

            $fp = $this->blogs($this->msg->getFirstPosts('blog', $this->pager->limit));
            if ($fp) {

                require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
                $rss = new suxRSS();
                $title = "{$this->r->title} | {$this->r->gtext['blog']}";
                $url = suxFunct::makeUrl('/blog', null, true);
                $rss->outputRSS($title, $url, null);

                foreach($fp as $item) {
                    $url = suxFunct::makeUrl('/blog/view/' . $item['thread_id'], null, true);
                    $rss->addOutputItem($item['title'], $url, $item['body_html']);
                }

                $this->tpl->assign('xml', $rss->saveXML());
            }

        }

        // Template
        header('Content-type: text/xml; charset=utf-8');
        $this->tpl->display('rss.tpl', $cache_id);

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


    // -----------------------------------------------------------------------
    // Protected functions for $this->tag()
    // -----------------------------------------------------------------------

    protected function countTaggedItems($id) {

        $db = suxDB::get();

        // Count
        $count_query = "
        SELECT COUNT(*) FROM messages
        INNER JOIN link_messages_tags ON link_messages_tags.messages_id = messages.id
        WHERE messages.thread_pos = 0 AND messages.blog = true  AND messages.draft = false AND link_messages_tags.tags_id = ?
        {$this->_dateSql()}
        ";
        $st = $db->prepare($count_query);
        $st->execute(array($id));
        return $st->fetchColumn();

    }


    protected function getTaggedItems($id, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT messages.* FROM messages
        INNER JOIN link_messages_tags ON link_messages_tags.messages_id = messages.id
        WHERE messages.thread_pos = 0 AND messages.blog = true  AND messages.draft = false AND link_messages_tags.tags_id = ?
        {$this->_dateSql()}
        ORDER BY messages.published_on DESC
        LIMIT {$limit} OFFSET {$start}
        ";

        $st = $db->prepare($query);
        $st->execute(array($id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    protected function getTaggedSidelist($id) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT messages.id, messages.thread_id, messages.title FROM messages
        INNER JOIN link_messages_tags ON link_messages_tags.messages_id = messages.id
        WHERE messages.thread_pos = 0 AND messages.blog = true  AND messages.draft = false AND link_messages_tags.tags_id = ?
        {$this->_dateSql()}
        ORDER BY messages.published_on DESC
        ";

        $st = $db->prepare($query);
        $st->execute(array($id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    // -----------------------------------------------------------------------
    // Protected functions for $this->category()
    // -----------------------------------------------------------------------


    protected function countCategorizedItems($id) {

        $db = suxDB::get();

        // Count
        $count_query = "
        SELECT COUNT(*) FROM messages
        INNER JOIN link_bayes_messages ON link_bayes_messages.messages_id = messages.id
        INNER JOIN bayes_documents ON link_bayes_messages.bayes_documents_id = bayes_documents.id
        INNER JOIN bayes_categories ON bayes_documents.bayes_categories_id = bayes_categories.id
        WHERE messages.thread_pos = 0 AND messages.blog = true  AND messages.draft = false AND bayes_categories.id = ?
        {$this->_dateSql()}
        ";
        $st = $db->prepare($count_query);
        $st->execute(array($id));
        return $st->fetchColumn();

    }


    protected function getCategorizedItems($id, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT messages.* FROM messages
        INNER JOIN link_bayes_messages ON link_bayes_messages.messages_id = messages.id
        INNER JOIN bayes_documents ON link_bayes_messages.bayes_documents_id = bayes_documents.id
        INNER JOIN bayes_categories ON bayes_documents.bayes_categories_id = bayes_categories.id
        WHERE messages.thread_pos = 0 AND messages.blog = true  AND messages.draft = false AND bayes_categories.id = ?
        {$this->_dateSql()}
        ORDER BY messages.published_on DESC
        LIMIT {$limit} OFFSET {$start}
        ";

        $st = $db->prepare($query);
        $st->execute(array($id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    protected function getCategorizedSidelist($id) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT messages.id, messages.thread_id, messages.title FROM messages
        INNER JOIN link_bayes_messages ON link_bayes_messages.messages_id = messages.id
        INNER JOIN bayes_documents ON link_bayes_messages.bayes_documents_id = bayes_documents.id
        INNER JOIN bayes_categories ON bayes_documents.bayes_categories_id = bayes_categories.id
        WHERE messages.thread_pos = 0 AND messages.blog = true  AND messages.draft = false AND bayes_categories.id = ?
        {$this->_dateSql()}
        ORDER BY messages.published_on DESC
        ";

        $st = $db->prepare($query);
        $st->execute(array($id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


}


?>