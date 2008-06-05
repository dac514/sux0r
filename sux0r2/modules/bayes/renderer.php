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

        static $tmp = array();
        if (count($tmp)) return $tmp; // Cache

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

        static $tmp = array();
        if (count($tmp)) return $tmp; // Cache

        foreach ($this->nb->getVectors() as $key => $val) {

            // Create a dropdown with <optgroup> array
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
    * Get category stats
    *
    * @return string html formated stats
    */
    function getCategoryStats() {

        static $tmp = array();
        if (count($tmp)) return $tmp; // Cache

        $cat = 0;
        $html = "<div id='bStats'><ul>\n";
        foreach ($this->nb->getVectors() as $key => $val) {
            $html .= "<li class='bStatsVec'>{$val['vector']}:</li>\n";
            $html .= "<ul>\n";
            foreach ($this->nb->getCategories($key) as $key2 => $val2) {
                $doc_count = $this->nb->getDocumentCount($key2);
                $html .= "<li class='bStatsCat'>{$val2['category']}:</li>";
                $html .= "<ul>\n";
                $html .= "<li class='bStatsDoc'>Documents: $doc_count</li><li class='bStatsTok'>Tokens: {$val2['token_count']}</li>\n";
                $html .= "</ul>\n";
                ++$cat;
            }
            $html .= "</ul>\n";
        }
        $html .= "</ul></div>\n";

        if (!$cat) return null;
        else return $html;
    }


    /**
    * Get documents
    *
    * @return array
    */
    function getDocuments() {

        static $tmp = array();
        if (count($tmp)) return $tmp; // Cache

        foreach ($this->nb->getVectors() as $key => $val) {
            foreach ($this->nb->getDocuments($key) as $key2 => $val2) {

                $tmp[$key2] = "{$key2} - {$val['vector']}, {$val2['category']}";

            }
        }

        return $tmp;

    }




}


?>