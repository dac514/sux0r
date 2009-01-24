<?php

/**
* societyRenderer
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

require_once(dirname(__FILE__) . '/../bayes/bayesRenderer.php');

class societyRenderer extends suxRenderer {

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
        $this->gtext = suxFunct::gtext('society'); // Language
        $this->user = new suxUser();
    }


    /**
    * Get identity
    *
    * @return array
    */
    function getIdentity() {

        return array(
            'me' => $this->gtext['me'],
            );

    }


    /**
    * Get friendship
    *
    * @return array
    */
    function getFriendship() {

        return array(
            'contact' => $this->gtext['contact'],
            'acquaintance' => $this->gtext['acquaintance'],
            'friend' => $this->gtext['friend'],
            '' => $this->gtext['none'],
            );

    }


    /**
    * Get physical
    *
    * @return array
    */
    function getPhysical() {

        return array(
            'met' => $this->gtext['met'],
            );

    }


    /**
    * Get professional
    *
    * @return array
    */
    function getProfessional() {

        return array(
            'co-worker' => $this->gtext['co_worker'],
            'colleague' => $this->gtext['colleague'],
            );

    }


    /**
    * Get geographical
    *
    * @return array
    */
    function getGeographical() {

        return array(
            'co-resident' => $this->gtext['co_resident'],
            'neighbor' => $this->gtext['neighbor'],
            '' => $this->gtext['none'],
            );

    }


    /**
    * Get family
    *
    * @return array
    */
    function getFamily() {

        return array(
            'child' => $this->gtext['child'],
            'parent' => $this->gtext['parent'],
            'sibling' => $this->gtext['sibling'],
            'spouse' => $this->gtext['spouse'],
            'kin' => $this->gtext['kin'],
            '' => $this->gtext['none'],
            );

    }


    /**
    * Get romantic
    *
    * @return array
    */
    function getRomantic() {

        return array(
            'muse' => $this->gtext['muse'],
            'crush' => $this->gtext['crush'],
            'date' => $this->gtext['date'],
            'sweetheart' => $this->gtext['sweetheart'],
            );

    }



}


?>