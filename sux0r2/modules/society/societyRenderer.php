<?php

/**
* societyRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class societyRenderer extends suxRenderer {

    // Object: suxUser()
    private $user;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
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


