<?php

/**
* menu
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function feeds_menu() {

    if (!isset($_SESSION['users_id'])) return null;

    // Check access
    $user = new suxUser();
    if (!$user->isRoot()) {
        $access = $user->getAccess('feeds');
        if ($access < $GLOBALS['CONFIG']['ACCESS']['feeds']['admin']) return null;
    }

    $query = 'SELECT COUNT(*) FROM rss_feeds WHERE draft = true ';
    $db = suxDB::get();
    $st = $db->query($query);

    $menu = array();
    $count = $st->fetchColumn();
    $text = suxFunct::gtext('feeds');

    $menu[$text['admin']] = suxFunct::makeUrl('/feeds/admin/');
    $tmp = "{$text['approve_2']} ($count)";
    $menu[$tmp] = suxFunct::makeUrl('/feeds/approve/');
    $menu[$text['new']] = suxFunct::makeUrl('/feeds/edit/');
    $menu[$text['purge']] = suxFunct::makeUrl('/feeds/purge/');

    return $menu;


}


?>