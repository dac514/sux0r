<?php

function sux($action, $params = null) {

    switch($action)
    {

    case 'profile' : // User profile

        include_once('suxUserProfile.php');
        if (!empty($params[0])) {
            $u = new suxUserProfile($params[0]);
            $u->render();
            break;
        }

    default:

        echo 'user';
        break;

    }

}

?>