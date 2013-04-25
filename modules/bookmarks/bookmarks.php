<?php

/**
* bookmarks
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class bookmarks extends bayesComponent {

    // Module name
    protected $module = 'bookmarks';

    // Object: suxUserNaiveBayesian()
    protected $nb;

    // Object: suxBookmarks()
    protected $bm;

    // Var: used by filter() method
    public $users_id;

    // Var: used by filter() method
    public $tag_id;


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->nb = new suxUserNaiveBayesian();
        $this->bm = new suxBookmarks();
        $this->r = new bookmarksRenderer($this->module); // Rend
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->r->bool['analytics'] = true; // Turn on analytics

    }


    function user($nickname, $alphasort) {

        $cache_id = null;
        $sort = array();

        // Get users_id based on nickname
        $user = $this->user->getByNickname($nickname);
        if (!$user) suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));
        // Needs to be in externally accessible variable for filter()
        $this->users_id = $user['users_id'];
        unset($user);

        // Establish order
        if ($alphasort) {
            $sort['sort'] = 'alpha'; // Sort, used in makeUrl() and passed as a hidden field to insert_bayesFilters()
            $this->bm->setOrder('title', 'ASC');
        }

        // Assign template variables
        $this->r->title .= " | {$this->r->gtext['bookmarks']} | $nickname";
        $this->r->text['form_url'] = suxFunct::makeUrl("/bookmarks/user/$nickname"); // Forum Url
        $this->tpl->assign('datesort_url', suxFunct::makeUrl("/bookmarks/user/$nickname"));
        $this->tpl->assign('alphasort_url', suxFunct::makeUrl("/bookmarks/user/$nickname", array('sort' => 'alpha')));
        $this->tpl->assign('sort', $sort);

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            // User has subscriptions, we need special JOIN queries
            $max = $this->countUserItems($this->users_id);
            $eval = '$this->getUserItems($this->users_id, $this->pager->limit, $start)';
            $this->r->arr['bookmarks']  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            // If $start is smaller than $max, then there are more results, we generate the approptiate pager link.
            if ($start < $max) {
                // Params
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                if ($alphasort)  $params['sort'] = 'alpha';
                // Pager link
                $this->r->text['pager'] = $this->pager->continueURL($start, suxFunct::makeUrl("/bookmarks/user/$nickname", $params));
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager, variable used in cache_id

            // "Cache Groups" using a vertical bar |
            $cache_id = "$nn|user|$nickname|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->isCached('scroll.tpl', $cache_id)) {

                // User has subscriptions, we need special JOIN queries
                $this->pager->setPages($this->countUserItems($this->users_id));
                $this->r->arr['bookmarks'] = $this->getUserItems($this->users_id, $this->pager->limit, $this->pager->start);

                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/bookmarks/user/$nickname", $sort));
                if (!count($this->r->arr['bookmarks'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        $this->tpl->assign('users_id', $this->users_id);

        $this->tpl->display('scroll.tpl', $cache_id);

    }



    /**
    * Tag
    */
    function tag($tag_id, $alphasort = false) {

        $cache_id = null;
        $sort = array();

        $tag = $this->tags->getByID($tag_id);
        if (!$tag) suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));
        // Needs to be in externally accessible variable for filter()
        $this->tag_id = $tag_id;

        // Establish order
        if ($alphasort) {
            $sort['sort'] = 'alpha'; // Sort, used in makeUrl() and passed as a hidden field to insert_bayesFilters()
            $this->bm->setOrder('title', 'ASC');
        }

        // Assign template variables
        $this->r->title .= " | {$this->r->gtext['bookmarks']} | {$this->r->gtext['tag']} | {$tag['tag']}";
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks/tag/' . $tag_id); // Form Url
        $this->tpl->assign('datesort_url', suxFunct::makeUrl("/bookmarks/tag/$tag_id"));
        $this->tpl->assign('alphasort_url', suxFunct::makeUrl("/bookmarks/tag/$tag_id", array('sort' => 'alpha')));
        $this->tpl->assign('sidetitle', $tag['tag']);
        $this->tpl->assign('sort', $sort);

        $count = $this->countTaggedItems($this->tag_id);

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $eval = '$this->getTaggedItems($this->tag_id, $this->pager->limit, $start)';
            $this->r->arr['bookmarks']  = $this->filter($count, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            // If $start is smaller than $count, then there are more results, we generate the approptiate pager link.
            if ($start < $count) {
                // Params
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                if ($alphasort)  $params['sort'] = 'alpha';
                // Pager link
                $this->r->text['pager'] = $this->pager->continueURL($start, suxFunct::makeUrl('/bookmarks/tag/'. $this->tag_id, $params));
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager, variable used in cache_id

            // "Cache Groups" using a vertical bar |
            if ($alphasort) $cache_id = "$nn|tags|{$this->tag_id}|alphasort|{$this->pager->start}";
            else $cache_id = "$nn|tags|{$this->tag_id}|datesort|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->isCached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($count);
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/bookmarks/tag/' . $this->tag_id, $sort));
                $this->r->arr['bookmarks'] = $this->getTaggedItems($this->tag_id, $this->pager->limit, $this->pager->start);

                if (!count($this->r->arr['bookmarks'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        $this->tpl->display('scroll.tpl', $cache_id);

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

        if (!$this->tpl->isCached('cloud.tpl', $cache_id)) {

            $db = suxDB::get();

            $link = $this->link->buildTableName('bookmarks', 'tags');
            $query = "
            SELECT tags.tag AS tag, tags.id AS id, COUNT(tags.id) AS quantity FROM tags
            INNER JOIN {$link} ON {$link}.tags_id = tags.id
            INNER JOIN bookmarks ON {$link}.bookmarks_id = bookmarks.id
            WHERE {$this->bm->sqlPublished()}
            GROUP BY tag, tags.id ORDER BY tag ASC
            ";

            $this->r->arr['tc'] = $this->tags->cloud($query);

            $this->r->title .= " | {$this->r->gtext['bookmarks']} | {$this->r->gtext['tag_cloud']}";

        }

        $this->tpl->display('cloud.tpl', $cache_id);

    }


    /**
    * Listing
    *
    * @param int $feeds_id a feed id
    */
    function listing($alphasort = false) {

        $cache_id = null;
        $sort = array();

        // Establish order
        if ($alphasort) {
            $sort['sort'] = 'alpha'; // Sort, used in makeUrl() and passed as a hidden field to insert_bayesFilters()
            $this->bm->setOrder('title', 'ASC');
        }

        // Assign template variables
        $this->r->title .= " | {$this->r->gtext['bookmarks']}";
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks');
        $this->tpl->assign('datesort_url', suxFunct::makeUrl('/bookmarks'));
        $this->tpl->assign('alphasort_url', suxFunct::makeUrl('/bookmarks', array('sort' => 'alpha')));
        $this->tpl->assign('sort', $sort);

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->bm->count();
            $eval = '$this->bm->get($this->pager->limit, $start)';
            $this->r->arr['bookmarks']  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            // If $start is smaller than $max, then there are more results, we generate the approptiate pager link.
            if ($start < $max) {
                // Params
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                if ($alphasort) $params['sort'] = 'alpha';
                // Pager link
                $this->r->text['pager'] = $this->pager->continueURL($start, suxFunct::makeUrl('/bookmarks/', $params));
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager, variable used in cache_id

            // "Cache Groups" using a vertical bar |
            if ($alphasort) $cache_id = "$nn|listing|alphasort|{$this->pager->start}";
            else $cache_id = "$nn|listing|datesort|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->isCached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->bm->count());
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/bookmarks/", $sort));
                $this->r->arr['bookmarks'] = $this->bm->get($this->pager->limit, $this->pager->start);

                if (!count($this->r->arr['bookmarks'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        $this->tpl->display('scroll.tpl', $cache_id);

    }


    /**
    * Display RSS Feed
    */
    function rss() {

        // Cache
        $cache_id = 'rss';
        $this->tpl->caching = 1;

        if (!$this->tpl->isCached('rss.tpl', $cache_id)) {

            $fp = $this->bm->get($this->pager->limit);
            if ($fp) {                
                $rss = new suxRSS();
                $title = "{$this->r->title} | {$this->r->gtext['bookmarks']}";
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
        INNER JOIN link__bookmarks__users ON link__bookmarks__users.bookmarks_id = bookmarks.id
        WHERE link__bookmarks__users.users_id = ? AND {$this->bm->sqlPublished()}
        ";
        $st = $db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    protected function getUserItems($users_id, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT bookmarks.* FROM bookmarks
        INNER JOIN link__bookmarks__users ON link__bookmarks__users.bookmarks_id = bookmarks.id
        WHERE link__bookmarks__users.users_id = ? AND {$this->bm->sqlPublished()}
        ORDER BY {$this->bm->sqlOrder()}
        LIMIT {$limit} OFFSET {$start} ";

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
        INNER JOIN link__bookmarks__tags ON link__bookmarks__tags.bookmarks_id = bookmarks.id
        WHERE link__bookmarks__tags.tags_id = ? AND {$this->bm->sqlPublished()}
        ";
        $st = $db->prepare($count_query);
        $st->execute(array($id));
        return $st->fetchColumn();

    }


    protected function getTaggedItems($id, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT bookmarks.* FROM bookmarks
        INNER JOIN link__bookmarks__tags ON link__bookmarks__tags.bookmarks_id = bookmarks.id
        WHERE link__bookmarks__tags.tags_id = ? AND {$this->bm->sqlPublished()}
        ORDER BY {$this->bm->sqlOrder()}
        LIMIT {$limit} OFFSET {$start} ";

        $st = $db->prepare($query);
        $st->execute(array($id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }



}


?>