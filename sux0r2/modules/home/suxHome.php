<?php

/**
* suxHome
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
require_once('renderer.php');

class suxHome {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    private $user; // suxUser
    private $module = 'home'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    */
    function __construct(suxUser $user) {

        $this->user = $user; // User

        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language
        $this->r = new renderer($this->module, $GLOBALS['CONFIG']['LANGUAGE']); // Renderer
        $this->r->text =& $this->gtext; // Language

    }


    /**
    * Display home
    */
    function display() {

        $this->r->header .= $this->r->someWittyName1();
        $this->tpl->assign_by_ref('r', $this->r);
        $output = $this->tpl->fetch('home.tpl');

        // Test
        //$this->r->header .= $this->r->someWittyName2();
        //$this->tpl->assign_by_ref('r', $this->r);
        //$output = $this->tpl->fetch('home2.tpl');

        echo $output;

    }


}


?>