<?php

function sux($action, $params = null) {

    unset($action, $params); // We don't use these here

    require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
    require_once(dirname(__FILE__) . '/../../includes/suxOpenID.php');
    require_once(dirname(__FILE__) . '/../../includes/suxUrl.php');

    $user = new suxUser();
    $openID = new suxOpenID($user);
    // $openID->profile['debug'] = true;
    $openID->profile['my_url'] = suxUrl::make('openid', true);

    // Pick a runmode
    $run_mode = (!empty($_REQUEST['openid_mode']) ? $_REQUEST['openid_mode'] : 'no') . '_mode';
    if (!preg_match('/^(\w|\-)+$/', $run_mode)) $run_mode = 'no_mode';

    // Go
    if (method_exists($openID, $run_mode)) $openID->$run_mode();
    else $openID->no_mode();

}

?>