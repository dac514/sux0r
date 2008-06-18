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
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('renderer.php');

class suxBlog  {

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $msg;

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

    }


    /**
    * Display
    */
    function display() {

        // Test
        $this->tpl->assign('url', 'http://www.sux0r.org/');
        $this->tpl->assign('title', 'sux0r - it sux0rs up all the web');

        // new dBug($this->msg->getThread(3));
        // new dBug($this->msg->getMessagesByUser(1));
        // new dBug($this->msg->getFirstPosts());
        // new dBug($this->msg->getFirstPostsByUser(1, 'blog'));
        // new dBug($this->msg->getFirstPostsByMonth(date('c'), 'blog'));
        // new dBug($this->msg->getRececentComments('blog'));
        // new dBug($this->msg->getCommentsCount(3));

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('scroll.tpl');

    }


}


?>