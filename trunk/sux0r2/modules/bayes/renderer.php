<?php

/**
* custom user module renderer
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
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxNaiveBayesian.php');

class renderer extends suxRenderer {


    public $profile = array(); // User profile

    private $user; // User object
    private $nb; // Naive Bayesian Object


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent

        $this->user = new suxUser();
        $this->nb = new suxNaiveBayesian();

    }


    /**
    * Get vectors
    *
    * @return array
    */
    function getVectors() {

        $tmp = array();
        foreach ($this->nb->getVectors() as $key => $val) {
            if (!in_array($val['vector'], $tmp)) $tmp[$key] = $val['vector'];
            else $tmp[$key] = "{$val['vector']} (id:$key)";
        }
        return $tmp;

    }


    /**
    * Get categories
    *
    * @return array
    */
    function getCategories() {

        // Create a dropdown with <optgroup> array

        $tmp = array();
        foreach ($this->nb->getVectors() as $key => $val) {

            $x = "{$val['vector']}";
            if (isset($tmp[$x])) $x = "{$val['vector']} (id:$key)";
            $y = array();
            foreach ($this->nb->getCategories($key) as $key2 => $val2) {
                $y[$key2] = "{$val2['category']}";
            }


            $tmp[$x] = $y;
        }

        return $tmp;

    }


    /**
    * Get documents
    *
    * @return array
    */
    function getDocuments() {

        foreach ($this->nb->getVectors() as $key => $val) {
            foreach ($this->nb->getDocuments($key) as $key2 => $val2) {

                $tmp[$key2] = "{$key2} - {$val['vector']}, {$val2['category']}";

            }
        }

        return $tmp;


    }



}


?>