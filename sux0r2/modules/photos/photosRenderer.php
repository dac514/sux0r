<?php

/**
* photosRenderer
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @copyright  2008 sux0r development group
* @license    http://www.gnu.org/licenses/agpl.html
*
*/

require_once(dirname(__FILE__) . '/../../includes/suxPhoto.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class photosRenderer extends suxRenderer {

    // Variables
    public $pho = array(); // Array of photo(s)
    public $fp = array(); // Alternater array
    public $album = array(); // Array of album(s)

    // Objects
    private $photo;
    private $user;

    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent
        $this->photo = new suxPhoto($module);
        $this->user = new suxUser();
    }



    /**
    * TinyMCE Initialization
    *
    * @see http://tinymce.moxiecode.com/
    * @global string $CONFIG['URL']
    * @global string $CONFIG['PATH']
    * @global string $CONFIG['LANGUAGE']
    * @param int $width optional width parameter for editor window
    * @param int $height optional height parameter for editor window
    * @return string the javascript code
    */
    function tinyMceEditor() {

        $init = '
        mode : "textareas",
        theme : "advanced",
        editor_selector : "mceEditor",
        plugins : "safari,inlinepopups,autosave",
        width: "100%",
        height: 100,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "undo,redo,|,bold,italic,underline,strikethrough,|,cleanup,code",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",
        theme_advanced_statusbar_location : "bottom",
        entity_encoding : "raw",
        relative_urls : false,
        inline_styles : false,
        ';
        return $this->tinyMce($init);

    }


    /**
    * Get users' albums
    *
    * @return array
    */
    function getAlbums() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        // Users id
        $users_id = $_SESSION['users_id'];
        if ($this->user->isRoot()) $users_id = null;
        else {
            $access = $this->user->getAccess('photos');
            if ($access >= $GLOBALS['CONFIG']['ACCESS'][$this->module]['admin']) $users_id = null;
        }

        // Get albums
        $albums = $this->photo->getAlbums($users_id, null, 0, true);

        $tmp[''] = '---';
        foreach ($albums as $album) {
            $tmp[$album['id']] = $album['title'];
        }

        return $tmp;

    }


    /**
    * Count photos
    *
    * @param int $photoalbums_id
    * @return int
    */
    function countPhotos($photoalbums_id) {

        return $this->photo->countPhotos($photoalbums_id);

    }


    /**
    * Get thumbnail
    *
    * @param int $photoalbums_id
    * @return int
    */
    function getThumbnail($photoalbums_id) {

        $image = null;
        $tmp = $this->photo->getThumbnail($photoalbums_id);
        if ($tmp) $image = suxFunct::makeUrl('/data/photos/' . rawurlencode($tmp['image']), null, true);

        return $image;

    }



}


// -------------------------------------------------------------------------
// Smarty {insert} functions
// -------------------------------------------------------------------------


/**
* Render edit links
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_editLinks($params) {

    if (!isset($_SESSION['users_id'])) return null;
    $br = null;
    if (isset($params['br'])) $br = '<br />';

    // Check that the user is allowed to create a new album
    $u = new suxUser();
    $text = suxFunct::gtext('photos');
    $html = '';

    $is_root = $u->isRoot();
    $access = $u->getAccess('photos');

    if (!$is_root) {
        if ($access < $GLOBALS['CONFIG']['ACCESS']['photos']['publisher'])
            return null;
    }

    if ($is_root || $access >= $GLOBALS['CONFIG']['ACCESS']['photos']['admin']) {
        $url = suxFunct::makeUrl('/photos/admin');
        $html .= "<a href='{$url}'>{$text['admin']}</a>$br ";
    }

    $url = suxFunct::makeUrl('/photos/album/edit/');
    $html .= "<a href='{$url}'>{$text['new']}</a>$br ";

    if (isset($params['div'])) return '<div class="editLinks">' . $html . '</div>';
    else return $html;

}


/**
* Render edit links
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_editLinks2($params) {

    if (!isset($_SESSION['users_id'])) return null;
    if (empty($params['album_id'])) return null;
    if (!filter_var($params['album_id'], FILTER_VALIDATE_INT) || $params['album_id'] < 1) return null;

    $br = null;
    if (isset($params['br'])) $br = '<br />';

    // Check that the user is allowed to edit this album
    $u = new suxUser();
    if (!$u->isRoot()) {
        $photo = new suxPhoto();
        $access = $u->getAccess('photos');
        if ($access < $GLOBALS['CONFIG']['ACCESS']['photos']['admin']) {
            if ($access < $GLOBALS['CONFIG']['ACCESS']['photos']['publisher']) return null;
            elseif (!$photo->isAlbumOwner($params['album_id'], $_SESSION['users_id'])) return null;
        }
    }

    $edit = suxFunct::makeUrl('/photos/album/edit/' . $params['album_id']);
    $annotate = suxFunct::makeUrl('/photos/album/annotate/' . $params['album_id']);
    $upload = suxFunct::makeUrl('/photos/upload/' . $params['album_id']);

    $text = suxFunct::gtext('photos');

    $html = '';
    $html .= "<a href='{$edit}'>{$text['edit_2']}</a>$br";
    $html .= "<a href='{$upload}'>{$text['upload']}</a>$br";
    $html .= "<a href='{$annotate}'>{$text['annotate_2']}</a>$br";

    if (isset($params['div'])) return '<div class="editLinks">' . $html . '</div>';
    else return $html;

}


?>