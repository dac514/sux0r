<?php

/**
* adminRenderer
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

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class adminRenderer extends suxRenderer {

    // Arrays
    public $gtext = array();

    // Objects
    private $user;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->gtext = suxFunct::gtext('admin'); // Language
        $this->user = new suxUser();

    }

    /**
    * Get a user's access levels
    *
    * @global $CONFIG['ACCESS']
    * @param int $users_id
    * @return string html
    */
    function getAccessLevels($users_id) {

        $access = array();
        foreach ($GLOBALS['CONFIG']['ACCESS']  as $key => $val) {
            $level = $this->user->getAccess($key, $users_id);
            if ($level) {
                $tmp = $GLOBALS['CONFIG']['ACCESS'][$key];
                $tmp = array_flip($tmp);
                $access[$key] = $tmp[$level];
            }
        }

        if (!count($access)) return null;

        $html = '';
        foreach ($access as $key => $val) {
            $key = ucfirst($key);
            $html .= "[ $key: $val ] ";
        }

        return $html;

    }


}


?>