<?php

/**
* custom home module renderer
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

require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class renderer extends suxRenderer {


    /**
    * Constructor
    */
    function __construct() {
        parent::__construct(); // Call parent
    }


    function someWittyName1() {

        $tmp = '
        <style>
        .leftside { width: 478px; }
        </style>
        ';

        return $tmp;

    }

    function someWittyName2() {

        $tmp = '
        <style>
        .leftside { width: 738px; }
        .rightside { margin-top: 10px; }
        </style>
        ';

        return $tmp;

    }


}


?>