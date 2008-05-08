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

    public $url;
    public $partition;
    public $text;
    public $bool;

    function __construct() {

        // --------------------------------------------------------------------
        // Sanity Check
        // --------------------------------------------------------------------

        if (!isset($GLOBALS['CONFIG'])) {
            die("Something is wrong, can't initialize without configuration.");
        }

        // --------------------------------------------------------------------
        // Go
        // --------------------------------------------------------------------

        $this->url = $GLOBALS['CONFIG']['URL'];
        $this->partition = $GLOBALS['CONFIG']['PARTITION'];

    }


}

?>