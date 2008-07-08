<?php

// Ajax
// Echo the owners of a vector

if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) {

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once(dirname(__FILE__) . '/../../initialize.php');
    require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
    require_once('bayesUser.php');

    $user = new suxUser();
    $nb = new bayesUser();

    $vectors = $nb->getVectorShares($_POST['id']);

    $users = null;
    foreach ($vectors as $val) {
        $u = $user->getUser($val['users_id']);
        $users .= $u['nickname'] . ', ';
    }

    $users = rtrim($users, ', ');

    echo $users;

}

?>