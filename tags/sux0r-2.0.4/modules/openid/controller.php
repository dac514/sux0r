<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function sux($action, $params = null) {

    unset($action, $params); // We don't use these here

    require_once('openid.php');

    $openID = new openid();
    //$openID->profile['debug'] = true;

    // Pick a runmode
    $run_mode = (!empty($_REQUEST['openid_mode']) ? $_REQUEST['openid_mode'] : 'no') . '_mode';
    if (!preg_match('/^(\w|\-)+$/', $run_mode)) $run_mode = 'no_mode';

    // Go
    if (method_exists($openID, $run_mode)) $openID->$run_mode();
    else $openID->no_mode();

}

?>