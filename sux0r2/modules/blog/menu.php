<?php

/**
* menu
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function blog_menu() {

    if (!isset($_SESSION['users_id'])) return null;

    // Check that the user is allowed to admin
    $user = new suxUser();
    $text = suxFunct::gtext('blog');

    $menu = array();
    $is_root = $user->isRoot();
    $access = $user->getAccess('blog');

    if (!$is_root) {
        if ($access < $GLOBALS['CONFIG']['ACCESS']['blog']['publisher'])
            return null;
    }


    if ($is_root || $access >= $GLOBALS['CONFIG']['ACCESS']['blog']['admin']) {
        $menu[$text['admin']] = suxFunct::makeUrl('/blog/admin');
    }

    $menu[$text['new']] = suxFunct::makeUrl('/blog/edit');

    return $menu;

}


