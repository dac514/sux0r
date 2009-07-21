<?php

/**
* home
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once('homeRenderer.php');
require_once(dirname(__FILE__) . '/../abstract.component.php');


class monjours extends component {

    // Module name
    protected $module = 'monjours';


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

        $this->tpl->display('monjours.tpl', $cache_id);

    }	
}


?>