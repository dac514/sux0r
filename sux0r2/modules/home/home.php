<?php

/**
* home
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class home extends component {

    // Module name
    protected $module = 'home';


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->r = new homeRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->r->bool['analytics'] = true; // Turn on analytics

    }


    /**
    * Display home
    */
    function display() {

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        $cache_id = "$nn|home";
        $this->tpl->caching = 1;

        $this->tpl->display('home.tpl', $cache_id);

    }


}


