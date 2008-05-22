<?php

/**
* suxRenderer
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

// See:
// http://www.phpinsider.com/smarty-forum/viewtopic.php?t=12683

require_once('suxTemplate.php');

class suxRenderer {

    public $url; // URL Prefix
    public $module; // Module
    public $partition; // sux0r parition name
    public $title; // Variable to put between <title> tags
    public $header; // Variable to keep header/text
    public $nav; // Variable to keep navlist
    public $text; // Variable to keep body/text
    public $bool; // Variable to keep bool values


    /**
    * Constructor
    *
    * @global string $CONFIG['URL']
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['TITLE']
    * @param string $module
    */
    function __construct($module) {

        $this->url = $GLOBALS['CONFIG']['URL'];
        $this->module = $module;
        $this->partition = $GLOBALS['CONFIG']['PARTITION'];
        $this->title = $GLOBALS['CONFIG']['TITLE'];
        $this->bool['analytics'] = true;

    }


    /**
    * Constructs a widget
    *
    * @global string $CONFIG['PATH']
    * @global string $CONFIG['PARTITION']
    * @param string $title a title
    * @param string $content html content
    * @param string $url URL for the title
    * @param string $image path to image (http://)
    * @param string $caption caption for image
    * @param string $url2 another url, for image
    * @return string the html code
    */
    function widget($title, $content, $url = null, $image = null, $caption = null, $url2 = null) {

        // Sanitize / Filter
        if ($url) {
            $url = suxFunct::canonicalizeUrl($url);
            if (!filter_var($url, FILTER_VALIDATE_URL)) $url = null;
        }
        if ($image) {
            $image = suxFunct::canonicalizeUrl($image);
            if (!filter_var($image, FILTER_VALIDATE_URL)) $image = null;
            if (!preg_match('/\.(jpe?g|gif|png)$/i', $image)) $image = null;
        }
        if ($url2) {
            $url2 = suxFunct::canonicalizeUrl($url2);
            if (!filter_var($url2, FILTER_VALIDATE_URL)) $url2 = null;
        }


        // Image manipulation
        $size = ($image) ? @getimagesize($image) : null;
        if ($size) {

            $width = $size[0]; // Keep width for template variable
            $alt = str_replace("'", "\'", $title); // Escape

            $tmp = '';
            if ($url) $tmp .= "<a href='{$url}'>";
            if ($url2) $tmp = "<a href='{$url2}'>"; // Overwrite
            $tmp .= "<img src='$image' alt='{$alt}' {$size[3]} />";
            if ($url || $url2) $tmp .= '</a>';

            $image = $tmp;

        }
        else $image = null;

        // Title manipulation
        if ($url) $title = "<a href='{$url}'>{$title}</a>";


        // Template
        $tpl = new suxTemplate($this->module);

        $path = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $GLOBALS['CONFIG']['PARTITION'];
        if (!file_exists("$path/widget.tpl")) $path = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r';

        $tpl->assign('title', $title);
        $tpl->assign('image', $image);
        $tpl->assign('caption', $caption);
        $tpl->assign('width', $width);
        $tpl->assign('content', $content);

        return $tpl->fetch("file:$path/widget.tpl");

    }


    /**
    * Construct a navigation container
    *
    * @param array $list key => name, val => url
    * @param string $selected match against key to add css selected
    * @return string the html code
    */
    function navlist($list, $selected = null) {

        if (!is_array($list)) return null;

        $html = "<div id='navcontainer'>\n";
        $html .= "<ul id='navlist'>\n";

        foreach ($list as $key => $val) {
            if ($key == $selected) $html .= "<li><a href='{$val}' class='selected'>{$key}</a></li>\n";
            else $html .= "<li><a href='{$val}'>{$key}</a></li>\n";
        }

        $html .= "</ul>\n";
        $html .= "</div>\n";
        $html .= "<div class='clearboth'></div>\n";

        return $html;

    }


    /**
    * Make URL Wrapper
    *
    * @param string $path controller value in /this/style
    * @param array $query http_build_query compatible array
    * @param bool $full return full url?
    * @return string url
    */
    function makeUrl($path, $query = null, $full = false) {

        return suxFunct::makeUrl($path, $query, $full);

    }


}

?>