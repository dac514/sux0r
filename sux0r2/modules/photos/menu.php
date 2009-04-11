<?php

/**
* menu
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function photos_menu() {

    if (!isset($_SESSION['users_id'])) return null;

    // Check that the user is allowed to admin
    $user = new suxUser();
    $text = suxFunct::gtext('photos');

    $menu = array();
    $is_root = $user->isRoot();
    $access = $user->getAccess('photos');


    if (!$is_root) {
        if ($access < $GLOBALS['CONFIG']['ACCESS']['photos']['publisher'])
            return null;
    }

    if ($is_root || $access >= $GLOBALS['CONFIG']['ACCESS']['photos']['admin']) {
        $menu[$text['admin']] = suxFunct::makeUrl('/photos/admin');
    }

    $menu[$text['new']] = suxFunct::makeUrl('/photos/album/edit/');
    $menu[$text['upload']] = suxFunct::makeUrl('/photos/upload/');

    return $menu;


}


?>