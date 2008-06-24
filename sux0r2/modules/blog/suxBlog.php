<?php

/**
* suxBlog
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

require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('renderer.php');


class suxBlog  {

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $msg;
    private $pager;
    private $liuk;

    // Variables
    public $gtext = array();
    private $module = 'blog';


    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new renderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->msg = new suxThreadedMessages();
        $this->link = new suxLink();
        $this->pager = new suxPager();

    }


    /**
    * Author
    */
    function author($author) {

        $u = $this->user->getUserByNickname($author);
        if ($u) {

            // Pager
            $this->pager->limit = 2;
            $this->pager->setStart();
            $this->pager->setPages($this->msg->countFirstPostsByUser($u['users_id'], 'blog'));

            // Assign
            $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/author/' . $author));
            $this->r->fp = $this->blogs($this->msg->getFirstPostsByUser($u['users_id'], 'blog', true, $this->pager->limit, $this->pager->start));
            $this->r->sidelist = $this->msg->getFirstPostsByUser($u['users_id'], 'blog'); // TODO: Too many blogs?
            $this->r->text['sidelist_title'] = ucwords($author);

        }


        // Template
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
        $this->pager->limit = 2;
        $this->pager->setStart();
        $this->pager->setPages($this->msg->countFirstPostsByMonth($datetime, 'blog'));

        // Assign
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog/month/' . $date));
        $this->r->fp = $this->blogs($this->msg->getFirstPostsByMonth($datetime, 'blog', true, $this->pager->limit, $this->pager->start));
        $this->r->sidelist = $this->msg->getFirstPostsByMonth($datetime, 'blog');
        $this->r->text['sidelist_title'] = date('F Y', strtotime($date));
        $this->r->archives = $this->msg->groupFirstPostsByMonths('blog');

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


    /**
    * Listing
    */
    function listing() {

        // Pager
        $this->pager->limit = 2;
        $this->pager->setStart();
        $this->pager->setPages($this->msg->countFirstPosts('blog'));

        // Assign

        // TODO: Move stuff like this to the renderer,
        // no point in initializing this if the user isn't going to use it
        // in the template

        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl('/blog'));
        $this->r->fp = $this->blogs($this->msg->getFirstPosts('blog', true, $this->pager->limit, $this->pager->start));
        $this->r->archives = $this->msg->groupFirstPostsByMonths('blog');
        $this->r->users = $this->msg->groupFirstPostsByUser('blog');
        foreach($this->r->users as &$val) {
            $u = $this->user->getUser($val['users_id']);
            $val['nickname'] = $u['nickname'];
        }

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }



    /**
    * @param array threaded messages
    * @return array
    */
    function blogs($msgs) {

        foreach($msgs as &$val) {
            $val['comments'] = $this->msg->getCommentsCount($val['thread_id']);
            $tmp = $this->user->getUser($val['users_id']);
            $val['nickname'] = $tmp['nickname'];

            /*
            1) Get the `link_bayes_messages` matching this messages_id
            2) Foreach linking bayes_document_id
            3) get the categories I can use (nb::isCategoryUser($cat_id, $users_id)
            4) stuff them into {$category_id} for template, append doc_id to {$link} string
            */

            $val['linked'] = '';
            $links = $this->link->getLinks('link_bayes_messages', 'messages', $val['id']);
            foreach($links as $val2) {
                $cat = $this->nb->getCategoriesByDocument($val2);
                foreach ($cat as $key => $val3) {
                    if ($this->nb->isCategoryUser($key, $_SESSION['users_id'])) {
                        $val['linked'] .= "$val2, ";
                        $val['category_id'][] = $key;
                    }
                }
            }
            $val['linked'] = rtrim($val['linked'], ', '); // Remove trailing comma

        }

        // new dBug($tmp);

        return $msgs;

    }


}


?>