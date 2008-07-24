<?php

/**
* photos
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

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('photosRenderer.php');

class photos {

    // Variables
    public $gtext = array();
    private $module = 'photos';

    // Objects
    public $tpl;
    public $r;
    private $user;


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new photosRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->user = new suxUser();
        $this->r->text =& $this->gtext;

    }


    /**
    * View photo
    */
    function view($id) {

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('view.tpl');

    }


    /**
    * List photos
    */
    function album($id) {

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('album.tpl');

    }


    /**
    * List photos
    */
    function listing() {

        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('list.tpl');

    }


}


?>