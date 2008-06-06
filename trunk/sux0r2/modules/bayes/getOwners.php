<?php

// Ajax
// Echo the owners of a vector

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once(dirname(__FILE__) . '/../../initialize.php');
    require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
    require_once('suxNbUser.php');

    $user = new suxUser();
    $nb = new suxNbUser();

    $vectors = $nb->getVectorShares($_GET['id']);

    $users = null;
    foreach ($vectors as $val) {
        $u = $user->getUser($val['users_id']);
        $users .= $u['nickname'] . ', ';
    }

    $users = rtrim($users, ', ');

    echo $users;

}

?>