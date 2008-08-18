<?php

/**
* bookmarksRenderer
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

require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class bookmarksRenderer extends suxRenderer {

    // Arrays
    public $fp = array(); // Array of first posts
    public $sidelist = array(); // Array of threads in sidebar
    public $gtext = array();
    public $tc = array(); // Tagcloud

    // Objects
    private $user;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->gtext = suxFunct::gtext('bookmarks'); // Language
        $this->user = new suxUser();

    }


    /**
    * Return tags associated to this bookmark
    *
    * @param int $id bookmark id
    * @return string html
    */
    function tags($id) {

        // ----------------------------------------------------------------
        // SQL
        // ----------------------------------------------------------------

        // Innerjoin query
        $innerjoin = '
        INNER JOIN link_bookmarks_tags ON link_bookmarks_tags.tags_id = tags.id
        ';

        // Select
        $query = "
        SELECT tags.id, tags.tag FROM tags
        {$innerjoin}
        WHERE link_bookmarks_tags.bookmarks_id = ?
        ";

        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($id));
        $cat = $st->fetchAll(PDO::FETCH_ASSOC);

        // ----------------------------------------------------------------
        // Html
        // ----------------------------------------------------------------

        foreach ($cat as $val) {
            $url = suxFunct::makeUrl('/bookmarks/tag/' . $val['id']);
            $html .= "<a href='{$url}'>{$val['tag']}</a>, ";
        }

        if (!$html) $html = $this->gtext['none'];
        else $html = rtrim($html, ', ');

        $html = "{$this->gtext['tags']}: " . $html . '';

        return $html;

    }


    /**
    * Return tag cloud
    *
    * @param array $tags key = tag, val = (quantity, id, size)
    * @return string html
    */
    function tagcloud($tags) {

        $html = '';
        foreach ($tags as $key => $val) {
            $url = suxFunct::makeURL('/bookmarks/tag/' . $val['id']);
            $html .= "<a href='{$url}' style='font-size: {$val['size']}%;' style='tag' s>{$key}</a> <span class='quantity' >({$val['quantity']})<span> ";
        }
        return $html;

    }


    /**
    * TinyMCE Initialization for bookmarks
    *
    * @see http://tinymce.moxiecode.com/
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



}


?>