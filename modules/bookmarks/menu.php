<?php

/**
* menu
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function bookmarks_menu() {

    if (!isset($_SESSION['users_id'])) return null;

    // Check access
    $user = new suxUser();
    if (!$user->isRoot()) {
        $access = $user->getAccess('feeds');
        if ($access < $GLOBALS['CONFIG']['ACCESS']['bookmarks']['admin']) return null;
    }

    $query = 'SELECT COUNT(*) FROM bookmarks WHERE draft = true ';
    $db = suxDB::get();
    $st = $db->query($query);

    $menu = array();
    $count = $st->fetchColumn();
    $text = suxFunct::gtext('bookmarks');


    $menu[$text['admin']] = suxFunct::makeUrl('/bookmarks/admin/');
    $tmp = "{$text['approve_2']} ($count)";
    $menu[$tmp] = suxFunct::makeUrl('/bookmarks/approve/');
    $menu[$text['new']] = suxFunct::makeUrl('/bookmarks/edit/');


    return $menu;


}


?>