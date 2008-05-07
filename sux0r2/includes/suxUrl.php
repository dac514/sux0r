<?php

/**
* suxUrl
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

class suxUrl {

    // No cloning or instantiating allowed
    final private function __construct() { }
    final private function __clone() { }


    static function make($path, $full = false) {

        if (!isset($GLOBALS['CONFIG'])) {
            die("Something is wrong, can't initialize without configuration.");
        }

        // Fix stupidties
        $path = trim($path);
        $path = ltrim($path, '/');
        $path = rtrim($path, '/');

        $tmp = '';
        if ($full) {
            // Autodetect ourself
            $s = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 's' : '';
            $host = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];
            if (($s && $port == "443") || (!$s && $port == "80") || preg_match("/:$port\$/", $host)) {
                $p = '';
            }
            else {
                $p = ':' . $port;
            }
            $tmp .= "http$s://$host$p";
        }
        $tmp .= $GLOBALS['CONFIG']['URL'];
        $tmp .= ($GLOBALS['CONFIG']['CLEAN_URL'] ? '/' : '/index.php?c=');
        $tmp .= $path;

        return $tmp;

    }


}


?>