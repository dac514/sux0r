<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

function sux($action, $params = null) {

    switch($action)
    {

    default:

        include_once('home.php');
        $home = new home();
        $home->display();

    }

}

?>