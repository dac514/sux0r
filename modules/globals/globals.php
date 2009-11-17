<?php

/**
* globals
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once('globalsRenderer.php');
require_once(dirname(__FILE__) . '/../abstract.component.php');


class globals extends component {

    // Module name
    protected $module = 'globals';


    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        $this->r = new globalsRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

    }


    /**
    * Display Banned Screen
    */
    function banned() {

        $this->tpl->caching = 1;
        $this->tpl->display('banned.tpl');

    }


    /**
    * Display Error 404
    */
    function e404() {

        $this->tpl->caching = 1;
        $this->tpl->display('404.tpl');

    }


}


?>