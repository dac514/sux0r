<?php

/**
* suxRenderer
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

// See:
// http://www.phpinsider.com/smarty-forum/viewtopic.php?t=12683

class suxRenderer {

    public $url; // URL Prefix
    public $partition; // sux0r parition name
    public $title; // Variable to put between <title> tags
    public $header; // Variable to keep header/text
    public $text; // Variable to keep body/text
    public $footer; // Variable to keep footer/text
    public $bool; // Variable to keep bool values


    /**
    * Constructor
    *
    * @global string $CONFIG['URL']
    * @global string $CONFIG['PARTITION']
    */
    function __construct() {

        $this->url = $GLOBALS['CONFIG']['URL'];
        $this->partition = $GLOBALS['CONFIG']['PARTITION'];
        $this->title = $GLOBALS['CONFIG']['TITLE'];
        $this->bool['analytics'] = true;

    }


}

?>