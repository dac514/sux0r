<?php

// Ajax
// Echo the owners of a vector

if (isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT)) {

    require_once(__DIR__ . '/../../config.php');
    require_once(__DIR__ . '/../../initialize.php');

    $user = new suxUser();
    $nb = new suxUserNaiveBayesian();

    $vectors = $nb->getVectorShares($_POST['id']);

    $users = null;
    foreach ($vectors as $val) {
        $u = $user->getByID($val['users_id']);
        $users .= $u['nickname'] . ', ';
    }

    $users = rtrim($users, ', ');

    echo $users;

}

