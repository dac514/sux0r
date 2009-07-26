<?php

/**
* controller
*
* @author     Santy Chumbe <chumbe@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function sux($action, $params = array()) {

    require_once('api.php');
    $api = new api();

    $action = $action . '_api';
    if (method_exists($api, $action)) $api->$action($params);
    else $api->welcome_api();

}

?>