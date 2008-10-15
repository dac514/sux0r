<?php

/**
* bookmarks
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
require_once(dirname(__FILE__) . '/../../includes/suxBookmarks.php');
require_once(dirname(__FILE__) . '/../../includes/suxTags.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php'); // includes bayesShared
require_once('bookmarksRenderer.php');

class bookmarks extends bayesShared {

    // Variables
    public $alphasort; // For filter
    public $users_id; // For filter
    public $tag_id; // For filter
    public $gtext = array();
    private $module = 'bookmarks';

    // Objects
    public $r;
    public $tpl;

    protected $pager;
    protected $nb;
    protected $bm;

    private $liuk;
    private $tags;
    private $user;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new bookmarksRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->r->bool['analytics'] = true; // Turn on analytics

        $this->user = new suxUser();
        $this->link = new suxLink();
        $this->nb = new bayesUser();
        $this->tags = new suxTags();
        $this->bm = new suxBookmarks();
        $this->pager = new suxPager();

    }


    function user($nickname, $alphasort) {

        // Get users_id based on nickname
        $user = $this->user->getUserByNickname($nickname);
        if (!$user) suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));

        // Needs to be in externally accessible variable for filter()
        $this->users_id = $user['users_id'];
        $this->alphasort = $alphasort;
        unset($user);

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl("/bookmarks/user/$nickname"); // Forum Url
        $this->tpl->assign('datesort_url', suxFunct::makeUrl("/bookmarks/user/$nickname"));
        $this->tpl->assign('alphasort_url', suxFunct::makeUrl("/bookmarks/user/$nickname", array('sort' => 'alpha')));
        $cache_id = false;

        // Sort, used in makeUrl() and passed as a hidden field to insert_bayesFilters()
        $sort = array();
        if ($this->alphasort) $sort['sort'] = 'alpha';
        $this->tpl->assign('sort', $sort);

        $this->r->title .= " | {$this->r->text['bookmarks']} | $nickname";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            // User has subscriptions, we need special JOIN queries
            $max = $this->countUserItems($this->users_id);
            $eval = '$this->getUserItems($this->users_id, $this->alphasort, $this->pager->limit, $start)';
            $this->r->fp  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                if ($this->alphasort)  $params['sort'] = 'alpha';
                $url = suxFunct::makeUrl("/bookmarks/user/$nickname", $params);
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
            $cache_id = "$nn|user|$nickname|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                // User has subscriptions, we need special JOIN queries
                $this->pager->setPages($this->countUserItems($this->users_id));
                $this->r->fp = $this->getUserItems($this->users_id, $this->alphasort, $this->pager->limit, $this->pager->start);

                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/bookmarks/user/$nickname", $sort));
                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        $this->tpl->assign('users_id', $this->users_id);
        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');


    }



    /**
    * Tag
    */
    function tag($tag_id, $alphasort = false) {

        $tag = $this->tags->getTag($tag_id);
        if (!$tag) suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));
        // Needs to be in externally accessible variable for filter()
        $this->tag_id = $tag_id;
        $this->alphasort = $alphasort;

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks/tag/' . $tag_id); // Form Url
        $this->tpl->assign('datesort_url', suxFunct::makeUrl("/bookmarks/tag/$tag_id"));
        $this->tpl->assign('alphasort_url', suxFunct::makeUrl("/bookmarks/tag/$tag_id", array('sort' => 'alpha')));
        $this->tpl->assign('sidetitle', $tag['tag']);
        $cache_id = false;

        // Sort, used in makeUrl() and passed as a hidden field to insert_bayesFilters()
        $sort = array();
        if ($this->alphasort) $sort['sort'] = 'alpha';
        $this->tpl->assign('sort', $sort);

        $count = $this->countTaggedItems($this->tag_id);

        $this->r->title .= " | {$this->r->text['bookmarks']} | {$this->r->text['tag']} | {$tag['tag']}";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $eval = '$this->getTaggedItems($this->tag_id, $this->alphasort, $this->pager->limit, $start)';
            $this->r->fp  = $this->filter($count, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            if ($start < $count) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                if ($this->alphasort)  $params['sort'] = 'alpha';
                $url = suxFunct::makeUrl('/bookmarks/tag/'. $this->tag_id, $params);
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
            if ($alphasort) $cache_id = "$nn|tags|{$this->tag_id}|alphasort|{$this->pager->start}";
            else $cache_id = "$nn|tags|{$this->tag_id}|datesort|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($count);
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/bookmarks/tag/' . $this->tag_id, $sort));
                $this->r->fp = $this->getTaggedItems($this->tag_id, $this->alphasort, $this->pager->limit, $this->pager->start);

                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

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

            $db = suxDB::get();

            $link = $this->link->getLinkTableName('bookmarks', 'tags');
            $query = "
            SELECT tags.tag AS tag, tags.id AS id, COUNT(tags.id) AS quantity FROM tags
            INNER JOIN {$link} ON {$link}.tags_id = tags.id
            INNER JOIN bookmarks ON {$link}.bookmarks_id = bookmarks.id
            WHERE bookmarks.draft = false {$this->_dateSql()}
            GROUP BY tag, tags.id ORDER BY tag ASC
            ";

            $this->r->tc = $this->tags->tagcloud($query);

            $this->r->title .= " | {$this->r->text['bookmarks']} | {$this->r->text['tag_cloud']}";

        }

        $this->tpl->display('cloud.tpl', $cache_id);

    }


    /**
    * Listing
    *
    * @param int $feeds_id a feed id
    */
    function listing($alphasort = false) {

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks'); // Form Url
        $this->tpl->assign('datesort_url', suxFunct::makeUrl('/bookmarks'));
        $this->tpl->assign('alphasort_url', suxFunct::makeUrl('/bookmarks', array('sort' => 'alpha')));
        $this->alphasort = $alphasort; // Needs to be in externally accessible variable for filter()
        $cache_id = false;

        // Sort, used in makeUrl() and passed as a hidden field to insert_bayesFilters()
        $sort = array();
        if ($this->alphasort) $sort['sort'] = 'alpha';
        $this->tpl->assign('sort', $sort);

        $this->r->title .= " | {$this->r->text['bookmarks']}";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->bm->countBookmarks();
            $eval = '$this->bm->getBookmarks($this->pager->limit, $start, $this->alphasort)';
            $this->r->fp  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                if ($alphasort) $params['sort'] = 'alpha'; // Sort

                $url = suxFunct::makeUrl('/bookmarks/', $params);
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
            if ($alphasort) $cache_id = "$nn|listing|alphasort|{$this->pager->start}";
            else $cache_id = "$nn|listing|datesort|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                // Start pager
                $this->pager->setStart();
                $this->pager->setPages($this->bm->countBookmarks());
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/bookmarks/", $sort));
                $this->r->fp = $this->bm->getBookmarks($this->pager->limit, $this->pager->start, $alphasort);

                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    /**
    * Display RSS Feed
    */
    function rss() {

        // Cache
        $cache_id = 'rss';
        $this->tpl->caching = 1;

        if (!$this->tpl->is_cached('rss.tpl', $cache_id)) {

            $fp = $this->bm->getBookmarks($this->pager->limit);
            if ($fp) {

                require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
                $rss = new suxRSS();
                $title = "{$this->r->title} | {$this->r->text['bookmarks']}";
                $url = suxFunct::makeUrl('/bookmarks', null, true);
                $rss->outputRSS($title, $url, null);

                foreach($fp as $item) {
                    $rss->addOutputItem($item['title'], $item['url'], $item['body_html']);
                }

                $this->tpl->assign('xml', $rss->saveXML());
            }

        }

        // Template
        header('Content-type: text/xml; charset=utf-8');
        $this->tpl->display('rss.tpl', $cache_id);

    }


    // -----------------------------------------------------------------------
    // Protected functions for $this->user()
    // -----------------------------------------------------------------------

    protected function countUserItems($users_id) {

        $db = suxDB::get();

        // Count
        $query = "
        SELECT COUNT(*) FROM bookmarks
        INNER JOIN link_bookmarks_users ON link_bookmarks_users.bookmarks_id = bookmarks.id
        WHERE link_bookmarks_users.users_id = ?
        {$this->_dateSql()}
        ";
        $st = $db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    protected function getUserItems($users_id, $alphasort, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT bookmarks.* FROM bookmarks
        INNER JOIN link_bookmarks_users ON link_bookmarks_users.bookmarks_id = bookmarks.id
        WHERE link_bookmarks_users.users_id = ?
        {$this->_dateSql()}
        ";
        if ($alphasort) $query .= 'ORDER BY bookmarks.title ASC ';
        else $query .= 'ORDER BY bookmarks.published_on DESC ';
        $query .= "LIMIT {$limit} OFFSET {$start} ";

        $st = $db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


    // -----------------------------------------------------------------------
    // Protected functions for $this->tag()
    // -----------------------------------------------------------------------

    protected function countTaggedItems($id) {

        $db = suxDB::get();

        // Count
        $count_query = "
        SELECT COUNT(*) FROM bookmarks
        INNER JOIN link_bookmarks_tags ON link_bookmarks_tags.bookmarks_id = bookmarks.id
        WHERE bookmarks.draft = false AND link_bookmarks_tags.tags_id = ?
        {$this->_dateSql()}
        ";
        $st = $db->prepare($count_query);
        $st->execute(array($id));
        return $st->fetchColumn();

    }


    protected function getTaggedItems($id, $alphasort, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT bookmarks.* FROM bookmarks
        INNER JOIN link_bookmarks_tags ON link_bookmarks_tags.bookmarks_id = bookmarks.id
        WHERE bookmarks.draft = false AND link_bookmarks_tags.tags_id = ?
        {$this->_dateSql()}
        ";
        if ($alphasort) $query .= 'ORDER BY title ASC ';
        else $query .= 'ORDER BY published_on DESC ';
        $query .= "LIMIT {$limit} OFFSET {$start} ";

        $st = $db->prepare($query);
        $st->execute(array($id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



}


?>