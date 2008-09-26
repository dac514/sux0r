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
            'me' => $this->text['me'],
            );

    }


    /**
    * Get friendship
    *
    * @return array
    */
    function getFriendship() {

        return array(
            'contact' => $this->text['contact'],
            'acquaintance' => $this->text['acquaintance'],
            'friend' => $this->text['friend'],
            '' => $this->text['none'],
            );

    }


    /**
    * Get physical
    *
    * @return array
    */
    function getPhysical() {

        return array(
            'met' => $this->text['met'],
            );

    }


    /**
    * Get professional
    *
    * @return array
    */
    function getProfessional() {

        return array(
            'co-worker' => $this->text['co_worker'],
            'colleague' => $this->text['colleague'],
            );

    }


    /**
    * Get geographical
    *
    * @return array
    */
    function getGeographical() {

        return array(
            'co-resident' => $this->text['co_resident'],
            'neighbor' => $this->text['neighbor'],
            '' => $this->text['none'],
            );

    }


    /**
    * Get family
    *
    * @return array
    */
    function getFamily() {

        return array(
            'child' => $this->text['child'],
            'parent' => $this->text['parent'],
            'sibling' => $this->text['sibling'],
            'spouse' => $this->text['spouse'],
            'kin' => $this->text['kin'],
            '' => $this->text['none'],
            );

    }


    /**
    * Get romantic
    *
    * @return array
    */
    function getRomantic() {

        return array(
            'muse' => $this->text['muse'],
            'crush' => $this->text['crush'],
            'date' => $this->text['date'],
            'sweetheart' => $this->text['sweetheart'],
            );

    }



}


?>