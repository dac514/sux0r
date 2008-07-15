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
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('feedsRenderer.php');


class feeds  {

    // Variables
    public $gtext = array();
    private $module = 'feeds';

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
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->msg = new suxThreadedMessages();
        $this->link = new suxLink();
        $this->nb = new bayesUser();

        $this->pager = new suxPager();
        $this->pager->limit = 2; // TODO, remove this value, it's for testing

    }



    /**
    * Listing
    */
    function listing() {

        if (list($vec_id, $cat_id, $threshold, $start) = $this->nb->isValidFilter()) {

            // Filtered results
            $max = $this->msg->countFirstPosts('blog');
            $eval = '$this->msg->getFirstPosts(\'blog\', true, $this->pager->limit, $start)';
            $this->r->fp  = $this->filter($max, $vec_id, $cat_id, $threshold, &$start, $eval); // Important: start must be reference

            if (count($this->r->fp) && $start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $url = suxFunct::makeUrl('/feeds/', $params);
                $this->r->text['pager'] = $this->pager->continueLink($start, $url);
            }

            $this->tpl->assign('filter', $cat_id);
            if ($threshold !== false) $this->tpl->assign('threshold', $threshold);

        }
        else {

            // Paged results
            $this->pager->setStart();
            $this->pager->setPages($this->msg->countFirstPosts('blog'));
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/feeds'));
            $this->r->fp = $this->msg->getFirstPosts('blog', true, $this->pager->limit, $this->pager->start);


        }

        // Forum Url
        $this->r->text['form_url'] = suxFunct::makeUrl('/feeds/');

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }



    /**
    * Filter
    */
    private function filter($max, $vec_id, $cat_id, $threshold, $start, $eval) {

        // -------------------------------------------------------------------
        // Get items based on score, variable paging
        // -------------------------------------------------------------------

        $fp = array(); // First posts array

        $init = $start;
        $i = 0;
        while ($i < $this->pager->limit) {

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
            if ($start == $init) $start = $start + ($this->pager->limit - 1);
            if ($i < $this->pager->limit && $start < ($max - $this->pager->limit)) {
                // Not enough first posts, keep looping
                ++$start;
            }
            else break;

        }
        ++$start;

        return $fp;

    }


}


?>