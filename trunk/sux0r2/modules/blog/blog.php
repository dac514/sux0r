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
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
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
        $this->tpl->assign_by_ref('r', $this->r);
        $cache_id = false;
        
        $u = $this->user->getUserByNickname($author);
        if ($u) {

            if (list($vec_id, $cat_id, $threshold, $start) = $this->nb->isValidFilter()) {

                // ---------------------------------------------------------------
                // Filtered results
                // ---------------------------------------------------------------

                $max = $this->msg->countFirstPostsByUser($u['users_id'], 'blog');
                $eval = '$this->msg->getFirstPostsByUser(' .$u['users_id'] . ', \'blog\', $this->pager->limit, $start)';
                $this->r->fp  = $this->blogs($this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval)); // Important: start must be reference

                if ($start < $max) {
                    if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                    else $params = array('filter' => $cat_id);
                    $url = suxFunct::makeUrl('/blog/author/'. $author, $params);
                    $this->r->text['pager'] = $this->pager->continueLink($start, $url);
                }

                $this->tpl->assign('filter', $cat_id);
                if ($threshold !== false) $this->tpl->assign('threshold', $threshold);

            }
            else {

                // ---------------------------------------------------------------
                // Paged results, cached
                // ---------------------------------------------------------------

                // Start pager
                $this->pager->setStart();

                // Get nickname
                if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
                else $nn = 'nobody';

                // "Cache Groups" using a vertical bar |
                $cache_id = $nn . '|author|' . $author . '|' . $this->pager->start;
                $this->tpl->caching = 1;

                if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                    $this->pager->setPages($this->msg->countFirstPostsByUser($u['users_id'], 'blog'));
                    $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/author/' . $author));
                    $this->r->fp = $this->blogs($this->msg->getFirstPostsByUser($u['users_id'], 'blog', $this->pager->limit, $this->pager->start));

                    if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

                }

            }

            // ---------------------------------------------------------------
            // Sidelist
            // ---------------------------------------------------------------

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->r->sidelist = $this->msg->getFirstPostsByUser($u['users_id'], 'blog'); // TODO: Too many blogs?
                $this->r->text['sidelist'] = ucwords($author);

            }


        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    /**
    * Tag
    */
    function tag($tag_id) {
        
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/tag/' . $tag_id); // Form Url
        $this->tpl->assign_by_ref('r', $this->r);
        $cache_id = false;
        
        $tag = $this->tags->getTag($tag_id);
        if (!$tag) suxFunct::redirect(suxFunct::makeUrl('/blog'));          
        $this->tag_id = $tag_id; // Needs to be in externally accessible variable for filter()    
        
        $count = $this->countTaggedItems($this->tag_id);                                
        
        if (list($vec_id, $cat_id, $threshold, $start) = $this->nb->isValidFilter()) {
            
            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------
            
            $eval = '$this->getTaggedItems($this->tag_id, $this->pager->limit, $start)';                    
            $this->r->fp  = $this->blogs($this->filter($count, $vec_id, $cat_id, $threshold, &$start, $eval)); // Important: start must be reference
            
            if ($start < $count) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl('/blog/tag/'. $this->tag_id, $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }
            
            $this->tpl->assign('filter', $cat_id);
            if ($threshold !== false) $this->tpl->assign('threshold', $threshold);
            
        }
        else {
            
            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------
            
            // Start pager
            $this->pager->setStart();
            
            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';
            
            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|tag|' . $this->tag_id . '|' . $this->pager->start;
            $this->tpl->caching = 1;
            
            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {
                
                $this->pager->setPages($count);
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/tag/' . $this->tag_id));                                                                           
                $this->r->fp = $this->blogs($this->getTaggedItems($this->tag_id, $this->pager->limit, $this->pager->start));
                
                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk
                
            }
            
        }
        
        // ---------------------------------------------------------------
        // Sidelist
        // ---------------------------------------------------------------
        
        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {
            
            $this->r->sidelist = $this->getTaggedSidelist($this->tag_id);
            $this->r->text['sidelist'] = $tag['tag'];
            
        }
        
        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');
        
    }


    /**
    * Tag cloud
    */
    function tagcloud() {

        $this->tpl->assign_by_ref('r', $this->r);

        // ---------------------------------------------------------------
        // Tagcloud, cached
        // ---------------------------------------------------------------

        $cache_id = 'tagcloud';
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('cloud.tpl', $cache_id)) {

            $db = suxDB::get();            

            $link = $this->link->getLinkTableName('messages', 'tags');
            $query = "
            SELECT tags.tag AS tag, tags.id AS id, COUNT(tags.id) AS quantity FROM tags
            INNER JOIN {$link} ON {$link}.tags_id = tags.id
            INNER JOIN messages ON {$link}.messages_id = messages.id
            WHERE messages.blog = 1 AND messages.draft = 0 {$this->_dateSql()}
            GROUP BY tag ORDER BY tag ASC
            ";

            $this->r->tc = $this->tags->tagcloud($query);

        }

        $this->tpl->display('cloud.tpl', $cache_id);

    }


    /**
    * Category
    */
    function category($cat_id) {

        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/category/' . $cat_id); // Form Url
        $this->tpl->assign_by_ref('r', $this->r);

        $cache_id = false;
        $c = $this->nb->getCategory($cat_id);

        if ($c) {

            $db = suxDB::get();            

            // ----------------------------------------------------------------
            // Reusable SQL
            // ----------------------------------------------------------------

            // Innerjoin query
            $innerjoin = '
            INNER JOIN link_bayes_messages ON link_bayes_messages.messages_id = messages.id
            INNER JOIN bayes_documents ON link_bayes_messages.bayes_documents_id = bayes_documents.id
            INNER JOIN bayes_categories ON bayes_documents.bayes_categories_id = bayes_categories.id
            ';                        

            // ----------------------------------------------------------------
            // SQL
            // ----------------------------------------------------------------

            // Count
            $count_query = "
            SELECT COUNT(*) FROM messages
            {$innerjoin}
            WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND bayes_categories.id = ?
            {$this->_dateSql()}
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
                {$this->_dateSql()}
                ORDER BY messages.published_on DESC
                ";

                if (list($vec_id, $cat_id2, $threshold, $start) = $this->nb->isValidFilter()) {

                    // ---------------------------------------------------------------
                    // Filtered results
                    // ---------------------------------------------------------------

                    $eval = '$this->_category("' . $limit_query . '", ' . $cat_id2 . ', $start)';
                    $this->r->fp  = $this->blogs($this->filter($count, $vec_id, $cat_id2, $threshold, &$start, $eval)); // Important: start must be reference

                    if ($start < $count) {
                        if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id2);
                        else $params = array('filter' => $cat_id2);
                        $url = suxFunct::makeUrl('/blog/category/'. $cat_id, $params);
                        $this->r->text['pager'] = $this->pager->continueLink($start, $url);
                    }

                    $this->tpl->assign('filter', $cat_id2);
                    if ($threshold !== false) $this->tpl->assign('threshold', $threshold);

                }
                else {

                    // ---------------------------------------------------------------
                    // Paged results, cached
                    // ---------------------------------------------------------------

                    // Start pager
                    $this->pager->setStart();

                    // Get nickname
                    if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
                    else $nn = 'nobody';

                    // "Cache Groups" using a vertical bar |
                    $cache_id = $nn . '|category|' . $cat_id . '|' . $this->pager->start;
                    $this->tpl->caching = 1;

                    if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                        $this->pager->setPages($count);
                        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/category/' . $cat_id));

                        if ($this->pager->start && $this->pager->limit) $limit_query .= "LIMIT {$this->pager->start}, {$this->pager->limit} ";
                        elseif ($this->pager->limit) $limit_query .= "LIMIT {$this->pager->limit} ";

                        $st = $db->prepare($limit_query);
                        $st->execute(array($cat_id));
                        $fp = $st->fetchAll(PDO::FETCH_ASSOC);
                        $this->r->fp = $this->blogs($fp);

                        if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

                    }
                }

                // ---------------------------------------------------------------
                // Sidelist
                // ---------------------------------------------------------------

                if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                    $select_query = "
                    SELECT messages.id, messages.thread_id, messages.title FROM messages
                    {$innerjoin}
                    WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND bayes_categories.id = ?
                    {$this->_dateSql()}
                    ORDER BY messages.published_on DESC
                    ";

                    $st = $db->prepare($select_query);
                    $st->execute(array($cat_id));
                    $sidelist = $st->fetchAll(PDO::FETCH_ASSOC);
                    $this->r->sidelist = $sidelist;
                    $this->r->text['sidelist'] = $c['category'];

                }
            }
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
        $this->tpl->assign_by_ref('r', $this->r);

        $cache_id = false;

        if (list($vec_id, $cat_id, $threshold, $start) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->msg->countFirstPostsByMonth($datetime, 'blog');
            $eval = '$this->msg->getFirstPostsByMonth(\'' . $datetime . '\', \'blog\', $this->pager->limit, $start)';
            $this->r->fp  = $this->blogs($this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval)); // Important: start must be reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl('/blog/month/'. $date, $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }

            $this->tpl->assign('filter', $cat_id);
            if ($threshold !== false) $this->tpl->assign('threshold', $threshold);

        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Start pager
            $this->pager->setStart();

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|month|' . date('Y-m', strtotime($date)) . '|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->msg->countFirstPostsByMonth($datetime, 'blog'));
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/month/' . $date));
                $this->r->fp = $this->blogs($this->msg->getFirstPostsByMonth($datetime, 'blog', $this->pager->limit, $this->pager->start));

                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        // ---------------------------------------------------------------
        // Sidelist
        // ---------------------------------------------------------------

        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

            $this->r->sidelist = $this->msg->getFirstPostsByMonth($datetime, 'blog');
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
        $this->tpl->assign_by_ref('r', $this->r);

        $cache_id = false;

        if (list($vec_id, $cat_id, $threshold, $start) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->msg->countFirstPosts('blog');
            $eval = '$this->msg->getFirstPosts(\'blog\', $this->pager->limit, $start)';
            $this->r->fp  = $this->blogs($this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval)); // Important: start must be reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl('/blog/', $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }

            $this->tpl->assign('filter', $cat_id);
            if ($threshold !== false) $this->tpl->assign('threshold', $threshold);


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Start pager
            $this->pager->setStart();

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            // "Cache Groups" using a vertical bar |
            $cache_id = $nn . '|listing|' . $this->pager->start;
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->msg->countFirstPosts('blog'));
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog'));
                $this->r->fp = $this->blogs($this->msg->getFirstPosts('blog', $this->pager->limit, $this->pager->start));

                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');


    }


    /**
    * View
    */
    function view($thread_id) {

        $this->tpl->assign_by_ref('r', $this->r);

        $this->pager->limit = 100;
        $this->pager->setStart();

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        // "Cache Groups" using a vertical bar |
        $cache_id = $nn . "|{$thread_id}|" . $this->pager->start;
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('view.tpl', $cache_id)) {

            $fp[] = $this->msg->getFirstPost($thread_id);

            if ($fp[0] === false) {
                // This is not a blog post, redirect
                suxFunct::redirect(suxFunct::makeUrl('/blog'));
            }

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
            $this->r->fp = $this->blogs($fp);
            $this->r->comments = $this->comments($thread);

        }

        $this->tpl->display('view.tpl', $cache_id);

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


    
    /**
    * Workaround function for $this->category()
    */
    protected function _category($q, $cat_id, $start) {

        $q .= "LIMIT {$start}, {$this->pager->limit} ";
        $db = suxDB::get();
        $st = $db->prepare($q);
        $st->execute(array($cat_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);
        
    }
        
    
    // -----------------------------------------------------------------------
    // Private functions for tag()
    // -----------------------------------------------------------------------
    
    protected function countTaggedItems($tag_id) {
        
        $db = suxDB::get();
        
        // Count
        $count_query = "
        SELECT COUNT(*) FROM messages
        INNER JOIN link_messages_tags ON link_messages_tags.messages_id = messages.id
        WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND link_messages_tags.tags_id = ?
        {$this->_dateSql()}
        ";
        $st = $db->prepare($count_query);
        $st->execute(array($tag_id));                
        return $st->fetchColumn();
        
    }   
    
    
    protected function getTaggedItems($tag_id, $limit, $start) {
        
        $db = suxDB::get();
        
        // Get Items
        $query = "
        SELECT messages.*, LENGTH(messages.body_plaintext) AS body_length FROM messages
        INNER JOIN link_messages_tags ON link_messages_tags.messages_id = messages.id
        WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND link_messages_tags.tags_id = ?
        {$this->_dateSql()}
        ORDER BY messages.published_on DESC
        LIMIT {$start}, {$limit}         
        ";
        
        $st = $db->prepare($query);
        $st->execute(array($tag_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);
        
    }   
    
    
    protected function getTaggedSidelist($tag_id) {
        
        $db = suxDB::get();
        
        // Get Items
        $query = "
        SELECT messages.id, messages.thread_id, messages.title FROM messages
        INNER JOIN link_messages_tags ON link_messages_tags.messages_id = messages.id
        WHERE messages.thread_pos = 0 AND messages.blog = 1  AND messages.draft = 0 AND link_messages_tags.tags_id = ?
        {$this->_dateSql()}
        ORDER BY messages.published_on DESC     
        ";
        
        $st = $db->prepare($query);
        $st->execute(array($tag_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);
        
    }     
    
   
}


?>