<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function sux($action, $params = null) {

    switch($action)
    {

    case'banned':

        $g = new globals();
        $g->banned();
        break;

    case'e404':

        $g = new globals();
        $g->e404();
        break;

    }

}

?>