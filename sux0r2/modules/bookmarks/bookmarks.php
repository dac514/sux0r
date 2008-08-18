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
require_once(dirname(__FILE__) . '/../../includes/suxBookmarks.php');
require_once(dirname(__FILE__) . '/../../includes/suxTags.php');
require_once('bookmarksRenderer.php');


class bookmarks  {

    // Variables
    public $gtext = array();
    private $module = 'bookmarks';

    // Objects
    private $bm;
    private $liuk;
    private $tags;
    private $pager;
    private $user;
    public $r;
    public $tpl;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new bookmarksRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->link = new suxLink();
        $this->tags = new suxTags();
        $this->bm = new suxBookmarks();
        $this->pager = new suxPager();

    }


    function user($nickname) {

        // TODO

    }


    /**
    * Tag
    */
    function tag($tag_id, $alphasort = false) {

        $this->tpl->assign_by_ref('r', $this->r);
        $cache_id = false;
        $tag = $this->tags->getTag($tag_id);

        if ($tag) {

            $db = suxDB::get();
            $db_driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

            // ----------------------------------------------------------------
            // Reusable SQL
            // ----------------------------------------------------------------

            // Innerjoin query
            $innerjoin = '
            INNER JOIN link_bookmarks_tags ON link_bookmarks_tags.bookmarks_id = bookmarks.id
            ';

            // Date query, database specic
            if ($db_driver == 'mysql') {
                $date = 'AND NOT published_on > \'' . date('Y-m-d H:i:s') . '\' ';
            }
            else {
                throw new Exception('Unsupported database driver');
            }

            // ----------------------------------------------------------------
            // SQL
            // ----------------------------------------------------------------

            // Count
            $count_query = "
            SELECT COUNT(*) FROM bookmarks
            {$innerjoin}
            WHERE bookmarks.draft = 0 AND link_bookmarks_tags.tags_id = ?
            {$date}
            ";
            $st = $db->prepare($count_query);
            $st->execute(array($tag_id));
            $count = $st->fetchColumn();

            if ($count) {

                // Select, with limits
                $limit_query = "
                SELECT bookmarks.* FROM bookmarks
                {$innerjoin}
                WHERE bookmarks.draft = 0 AND link_bookmarks_tags.tags_id = ?
                {$date}
                ";

                // Order
                if ($alphasort) $limit_query .= 'ORDER BY title DESC ';
                else $limit_query .= 'ORDER BY published_on DESC ';

                // ---------------------------------------------------------------
                // Paged results, cached
                // ---------------------------------------------------------------

                // "Cache Groups" using a vertical bar |
                if ($alphasort) $cache_id = "tags|$tag_id|alphasort|{$this->pager->start}";
                else $cache_id = "tags|$tag_id|datesort|{$this->pager->start}";
                $this->tpl->caching = 0; // TODO: Turn on caching

                if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                    // Start pager
                    $this->pager->setStart();

                    // Some defaults
                    $this->tpl->assign('datesort_url', suxFunct::makeUrl("/bookmarks/tag/$tag_id"));
                    $this->tpl->assign('alphasort_url', suxFunct::makeUrl("/bookmarks/tag/$tag_id", array('sort' => 'alpha')));

                    $sort = array();
                    if ($alphasort) $sort['sort'] = 'alpha';

                    $this->pager->setPages($count);
                    $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/bookmarks/tag/$tag_id", $sort));

                    if ($this->pager->start && $this->pager->limit) $limit_query .= "LIMIT {$this->pager->start}, {$this->pager->limit} ";
                    elseif ($this->pager->limit) $limit_query .= "LIMIT {$this->pager->limit} ";

                    $st = $db->prepare($limit_query);
                    $st->execute(array($tag_id));
                    $this->r->fp = $st->fetchAll(PDO::FETCH_ASSOC);

                    if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk


                }

            }


        }

        $this->tpl->display('scroll.tpl', $cache_id);

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
            $db_driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

            // Date query, database specic
            if ($db_driver == 'mysql') {
                $date = 'AND NOT published_on > \'' . date('Y-m-d H:i:s') . '\' ';
            }
            else {
                throw new Exception('Unsupported database driver');
            }


            $link = $this->link->getLinkTableName('bookmarks', 'tags');
            $query = "
            SELECT tags.tag AS tag, tags.id AS id, COUNT(tags.id) AS quantity FROM tags
            INNER JOIN {$link} ON {$link}.tags_id = tags.id
            INNER JOIN bookmarks ON {$link}.bookmarks_id = bookmarks.id
            WHERE bookmarks.draft = 0 {$date}
            GROUP BY tag ORDER BY tag ASC
            ";

            $this->r->tc = $this->link->tagcloud($query);

        }

        $this->tpl->display('cloud.tpl', $cache_id);

    }


    /**
    * Listing
    *
    * @param int $feeds_id a feed id
    */
    function listing($alphasort = false) {

        $this->tpl->assign_by_ref('r', $this->r);
        $cache_id = false;

        // ---------------------------------------------------------------
        // Paged results, cached
        // ---------------------------------------------------------------

        // "Cache Groups" using a vertical bar |
        if ($alphasort) $cache_id = "listing|alphasort|{$this->pager->start}";
        else $cache_id = "listing|datesort|{$this->pager->start}";
        $this->tpl->caching = 0; // TODO: Turn on caching

        if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

            // Start pager
            $this->pager->setStart();

            // Some defaults
            $this->tpl->assign('datesort_url', suxFunct::makeUrl('/bookmarks'));
            $this->tpl->assign('alphasort_url', suxFunct::makeUrl('/bookmarks', array('sort' => 'alpha')));

            $sort = array();
            if ($alphasort) $sort['sort'] = 'alpha';

            $this->pager->setPages($this->bm->countBookmarks());
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/bookmarks/", $sort));
            $this->r->fp = $this->bm->getBookmarks($this->pager->limit, $this->pager->start, $alphasort);

            if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

        }

        $this->tpl->display('scroll.tpl', $cache_id);

    }


}


?>