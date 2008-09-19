<?php

/**
* admin
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

require_once(dirname(__FILE__) . '/../../includes/suxPager.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('adminRenderer.php');


class admin {

    // Variables
    public $per_page = 100;
    private $module = 'admin';

    // Objects
    public $r;
    public $tpl;
    private $pager;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new adminRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        $this->user = new suxUser();
        $this->pager = new suxPager();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/home'));

    }


    function userlist() {

        $this->pager->limit = $this->per_page;
        $this->pager->setStart();

        $this->pager->setPages($this->user->countUsers());
        $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/admin"));
        $this->r->ulist = $this->user->getUsers($this->pager->limit, $this->pager->start);

        $this->tpl->display('userlist.tpl');

    }


}


?>