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

    public $pho = array(); // Array of photos

    // Objects
    private $photo;

    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {
        parent::__construct($module); // Call parent
        $this->photo = new suxPhoto($module);
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

        $albums = $this->photo->getAlbums($_SESSION['users_id'], null, 0, true);

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
        if ($tmp) $image = suxFunct::makeUrl('/data/photos/' . $tmp['image'], null, true);

        return $image;

    }



}


?>