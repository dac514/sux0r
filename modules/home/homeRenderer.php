<?php

/**
* homeRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class homeRenderer extends suxRenderer {

    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent
    }


}


?>