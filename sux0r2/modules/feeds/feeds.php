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
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('feedsRenderer.php');


class feeds  {

    // Variables
    public $gtext = array();
    private $module = 'feeds';

    // Objects
    private $liuk;
    private $rss;
    private $nb;
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
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->rss = new suxRSS();
        $this->link = new suxLink();
        $this->nb = new bayesUser();
        $this->pager = new suxPager();

    }

    function author($author) {

    }



    /**
    * Listing
    *
    * @param int $feeds_id a feed id
    */
    function listing($feeds_id = null) {

        $this->r->text['form_url'] = suxFunct::makeUrl("/feeds/$feeds_id"); // Forum Url
        $this->tpl->assign_by_ref('r', $this->r);

        $cache_id = false;

        if (list($vec_id, $cat_id, $threshold, $start) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            $max = $this->rss->countItems($feeds_id);
            $eval = '$this->rss->getItems(' . ($feeds_id ? $feeds_id : 'null') . ', $this->pager->limit, $start)';
            $this->r->fp  = $this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval); // Important: start must be reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl("/feeds/$feeds_id", $params);
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
            $cache_id = "$nn|listing|$feeds_id|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                $this->pager->setPages($this->rss->countItems($feeds_id));
                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/feeds/$feeds_id"));
                $this->r->fp = $this->rss->getItems($feeds_id, $this->pager->limit, $this->pager->start);

                if (!count($this->r->fp)) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }



    /**
    * Filter
    */
    private function filter($max, $vec_id, $cat_id, $threshold, $start, $eval) {

        // -------------------------------------------------------------------
        // Get items based on score, variable paging
        // -------------------------------------------------------------------

        $fp = array(); // First posts array

        // Force timeout if this operation takes too long
        $timer = microtime(true);
        $timeout_max = ini_get('max_execution_time') * 0.333333;
        if ($timeout_max > 30) $timeout_max = 30;

        // Start filtering
        $i = 0;
        $limit = $this->pager->limit;
        while ($i < $limit) {

            $tmp = array();
            eval('$tmp = ' . $eval . ';'); // $fp is transformed here, by $eval
            $fp = array_merge($fp, $tmp);

            foreach ($fp as $key => $val) {
                if (!$this->nb->passesThreshold($threshold, $vec_id, $cat_id, $val['body_plaintext'])) {
                    unset($fp[$key]);
                    continue;
                }
            }

            $i = count($fp);
            $start = $start + $this->pager->limit;

            // new dBug("i: $i");
            // new dBug("next start: $start");
            // new dBug("limit: $limit");
            // new dBug("max: $max");
            // new dBug('---');

            if ($i < $limit && $start < ($max) && ($timer + $timeout_max) > microtime(true)) {
                // Not enough first posts, keep looping
                $this->pager->limit = 1;
            }
            else break;

        }
        $this->pager->limit = $limit; // Restore limit

        return $fp;

    }


}


?>