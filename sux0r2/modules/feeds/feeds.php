<?php

/**
* feeds
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
* @author     Dac Chartrand <dac.chartrand@gmail.com>c
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php'); // includes bayesShared
require_once('feedsRenderer.php');


class feeds extends bayesShared {

    // Variables
    public $users_id; // For filter
    public $gtext = array();
    private $module = 'feeds';

    // Objects
    public $r;
    public $tpl;

    protected $rss;
    protected $nb;
    protected $pager;

    private $liuk;
    private $user;



    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->rss = new suxRSS();
        $this->link = new suxLink();
        $this->nb = new bayesUser();
        $this->pager = new suxPager();

        // This module has config variables, load them
        $this->tpl->config_load('my.conf', $this->module);

    }


    function user($nickname) {

        // Get users_id based on nickname
        $user = $this->user->getUserByNickname($nickname);
        if (!$user) suxFunct::redirect(suxFunct::makeUrl('/feeds'));
        $this->users_id = $user['users_id']; // Needs to be in externally accessible variable for filter()
        unset($user);

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl("/feeds/user/$nickname"); // Forum Url
        $cache_id = false;

        $this->r->title .= " | {$this->r->text['feeds']} | $nickname";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            // User has subscriptions, we need special JOIN queries
            $max = $this->countUserItems($this->users_id);
            $eval = '$this->getUserItems($this->users_id, $this->pager->limit, $start)';
            $this->r->fp  = $this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval, $search); // Important: start must be reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl("/feeds/user/$nickname", $params);
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
                $this->r->fp = $this->getUserItems($this->users_id, $this->pager->limit, $this->pager->start);

                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/feeds/user/$nickname"));
                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        $this->tpl->assign('users_id', $this->users_id);
        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');


    }



    /**
    * Listing
    *
    * @param int $feeds_id a feed id
    */
    function listing($feeds_id = null) {

        // Check if the user has any subscriptions
        $subscriptions = array();
        if (isset($_SESSION['users_id'])) {
            $subscriptions = $this->link->getLinks('link_rss_users', 'users', $_SESSION['users_id']);
            $this->tpl->assign('users_id', $_SESSION['users_id']);
        }

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl("/feeds/$feeds_id"); // Forum Url
        $cache_id = false;

        // Title
        if ($feeds_id) {
            $this->r->title .= " | {$this->r->text['feed']}";
            $tmp = $this->rss->getFeed($feeds_id);
            if ($tmp) $this->r->title .= " | {$tmp['title']}";
        }
        else {
            $this->r->title .= " | {$this->r->text['feeds']}";
        }

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            if ($feeds_id || !count($subscriptions)) {
                // Regular queries
                $max = $this->rss->countItems($feeds_id);
                $eval = '$this->rss->getItems(' . ($feeds_id ? $feeds_id : 'null') . ', $this->pager->limit, $start)';
            }
            else {
                // User has subscriptions, we need special JOIN queries
                $max = $this->countUserItems($_SESSION['users_id']);
                $eval = '$this->getUserItems($_SESSION[\'users_id\'], $this->pager->limit, $start)';
            }

            $this->r->fp  = $this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval, $search);  // Important: start must be reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl("/feeds/$feeds_id", $params);
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
            $cache_id = "$nn|listing|$feeds_id|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                if ($feeds_id || !count($subscriptions)) {
                    // Regular queries
                    $this->pager->setPages($this->rss->countItems($feeds_id));
                    $this->r->fp = $this->rss->getItems($feeds_id, $this->pager->limit, $this->pager->start);
                }
                else {
                    // User has subscriptions, we need special JOIN queries
                    $this->pager->setPages($this->countUserItems($_SESSION['users_id']));
                    $this->r->fp = $this->getUserItems($_SESSION['users_id'], $this->pager->limit, $this->pager->start);
                }

                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/feeds/$feeds_id"));
                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    // -----------------------------------------------------------------------
    // Protected functions for $this->user() & this->listing()
    // -----------------------------------------------------------------------

    protected function countUserItems($users_id) {

        $db = suxDB::get();

        // Count
        $query = "
        SELECT COUNT(*) FROM rss_items
        INNER JOIN rss_feeds on rss_feeds.id = rss_items.rss_feeds_id
        INNER JOIN link_rss_users ON link_rss_users.rss_feeds_id = rss_feeds.id
        WHERE link_rss_users.users_id = ?
        ";
        $st = $db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    protected function getUserItems($users_id, $limit, $start) {

        $db = suxDB::get();

        // Get Items
        $query = "
        SELECT rss_items.* FROM rss_items
        INNER JOIN rss_feeds on rss_feeds.id = rss_items.rss_feeds_id
        INNER JOIN link_rss_users ON link_rss_users.rss_feeds_id = rss_feeds.id
        WHERE link_rss_users.users_id = ?
        ORDER BY rss_items.published_on DESC, rss_items.id DESC
        LIMIT {$start}, {$limit}
        ";

        $st = $db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchAll(PDO::FETCH_ASSOC);

    }


}


?>