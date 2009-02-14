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

    public $module; // Module
    public $xhtml_header; // Full path to xhtml_header.tpl
    public $xhtml_footer; // Full path to xhtml_footer.tpl

    // Text
    public $url; // Site URL Prefix, e.g. /my/sux0r
    public $partition; // sux0r parition name
    public $title; // Variable to put between <title> tags
    public $sitename; // Alternate title variable
    public $stylesheets; // Variable to put stylesheets/text
    public $header; // Variable to put header/text

    // Arrays
    public $nav = array(); // Variable to keep navlist
    public $gtext = array(); // Variable to store gtext in
    public $text  = array(); // Variable to store dynamic text in
    public $arr = array(); // Variable to keep arrays
    public $bool = array(); // Variable to keep bool values


    /**
    * Constructor
    *
    * @global string $CONFIG['LANGUAGE']
    * @global string $CONFIG['PATH']
    * @global string $CONFIG['URL']
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['TITLE']
    * @param string $module
    */
    function __construct($module) {

        // Module
        $this->module = $module;

        // Partition
        if (!empty($_SESSION['partition'])) $this->partition = $_SESSION['partition'];
        else $this->partition  = $GLOBALS['CONFIG']['PARTITION'];

        // Path to XTHML header & footer templates
        $this->xhtml_header = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $this->partition  . '/globals/xhtml_header.tpl';
        if (!file_exists($this->xhtml_header)) $this->xhtml_header = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/globals/xhtml_header.tpl';
        $this->xhtml_footer = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $this->partition  . '/globals/xhtml_footer.tpl';
        if (!file_exists($this->xhtml_footer)) $this->xhtml_footer = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/globals/xhtml_footer.tpl';

        // Defaults
        $this->url = $GLOBALS['CONFIG']['URL'];
        $this->title = $GLOBALS['CONFIG']['TITLE'];
        $this->sitename = $GLOBALS['CONFIG']['TITLE'];
        $this->bool['analytics'] = false;

        // Stylesheets
        $this->stylesheets = "<link rel='stylesheet' type='text/css' href='{$this->url}/media/{$this->partition}/css/base.css' />\n";
        if (file_exists($GLOBALS['CONFIG']['PATH'] . "/media/{$this->partition}/css/{$this->module}.css")) {
            $this->stylesheets .= "<link rel='stylesheet' type='text/css' href='{$this->url}/media/{$this->partition}/css/{$this->module}.css' />\n";
        }

        // Gtext
        $this->gtext = suxFunct::gtext($module);

    }


    /**
    * Assign, used to access this object's variables from inside a template
    *
    * @param string $variable the public variable to work with
    * @param string $value content
    * @param string|bool $k either key, or append
    */
    function assign($variable, $value, $k = false) {

        // Array
        if (is_array($this->$variable)) {
            if (!$k) return;
            else {
                $this->$variable[$k] = $value;
                return;
            }
        }

        // Text
        if ($k) $this->$variable .= $value; // Append
        else $this->$variable = $value;

    }


    /**
    * Hash
    *
    * @global string $CONFIG['SALT']
    * @param string $v1
    * @param string $v2 optional
    * @param string $v3 optional
    * @param string $v4 optional
    * @param string $v5 optional
    * @param string $v6 optional
    * @return string md5 hash of variables concatenated with salt
    */
    function integrityHash($v1, $v2 = null, $v3 = null, $v4 = null, $v5 = null, $v6 = null) {

        return md5($v1 . $v2 . $v3 . $v4 . $v5 . $v6 . $GLOBALS['CONFIG']['SALT']);

    }


    /**
    * Detect $_POST
    *
    * @return bool
    */
    function detectPOST() {

        if (isset($_POST) && count($_POST)) return true;
        else return false;

    }


    /**
    * Check if a user is logged in
    */
    function isLoggedIn() {

        return isset($_SESSION['users_id']) ? true : false;

    }



    /**
    * Constructs a widget
    *
    * @global string $CONFIG['PATH']
    * @param string $title a title
    * @param string $content html content
    * @param string $url URL for the title
    * @param string $image path to image (http://)
    * @param string $caption caption for image
    * @param string $url2 another url, for image
    * @param string $floater class for image encapsulation
    * @return string the html code
    */
    function widget($title, $content, $url = null, $image = null, $caption = null, $url2 = null, $floater = 'floatright') {

        // Sanitize / Filter
        if ($url) {
            $url = suxFunct::canonicalizeUrl($url);
            if (!filter_var($url, FILTER_VALIDATE_URL)) $url = null;
        }
        if ($image) {
            $image = suxFunct::canonicalizeUrl($image);
            if (!filter_var($image, FILTER_VALIDATE_URL)) $image = null;
            // The server can be crippled if getimagesize() recursively points
            // to itself (example: . $image = /index.php) so we enforce image
            // extensions to avoid this
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
            $alt = str_replace("'", "", strip_tags($title)); // Escape

            $tmp = '';
            if ($url) $tmp .= "<a href='{$url}' class='noBg'>";
            if ($url2) $tmp = "<a href='{$url2}' class='noBg'>"; // Overwrite
            $tmp .= "<img src='$image' alt='{$alt}' {$size[3]} />";
            if ($url || $url2) $tmp .= '</a>';

            $image = $tmp;

        }
        else $image = null;

        // Title manipulation
        if ($url) $title = "<a href='{$url}'>{$title}</a>";


        // Template
        $tpl = new suxTemplate($this->module);

        $tpl->assign('title', $title);
        $tpl->assign('image', $image);
        $tpl->assign('caption', $caption);
        $tpl->assign('width', $width);
        $tpl->assign('content', $content);
        $tpl->assign('floater', $floater);

        $path = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $this->partition . '/globals/';
        if (!file_exists("$path/widget.tpl")) $path = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/globals/';
        return $tpl->fetch("file:$path/widget.tpl");

    }


    /**
    * Highlight HTML
    *
    * @global string $_GET['search']
    * @param string $html the text to highlight
    * @param string $search
    * @return string
    */
    static function highlight($html, $search = null) {

        if (!$search) {
            // Try to fallback on GET query
            if (!isset($_GET['search']) || !trim($_GET['search'])) return $html;
            else $search = $_GET['search'];
        }

        $search = trim(mb_strtoupper(strip_tags($search)));

        $words = array();
        $rawtokens = mb_split("\W", $search);
        foreach ($rawtokens as $v) {
            if (trim($v)) $words[] = $v;
        }

        $replacements = array();
        foreach($words as $word) {
            $replacements[] = "<span class='highlight'>$word</span>";
        }

        // Split up the content into chunks delimited by a reasonable aproximation
        // of what an HTML element looks like

        $parts = preg_split("{(<(?:\"[^\"]*\"|'[^']*'|[^'\">])*>)}", $html, -1, PREG_SPLIT_DELIM_CAPTURE); // Unlimited number of chunks
        foreach ($parts as $i => $part) {
            // Skip if this part is an HTML element
            if (isset($part[0]) && ($part[0] == '<')) { continue; }
            // Wrap the words with <span/>s
            $parts[$i] = str_ireplace($words, $replacements, $part); // TODO, make multi-byte compatible
        }

        $html = implode('', $parts);

        return $html;

    }


    /**
    * Construct a navigation div
    *
    * @global bool $CONFIG['CLEAN_URL']
    * @global string $CONFIG['URL']
    * @param array $list key => name, val => url
    * @return string the html code
    */
    function navlist($list = null) {

        $html = "<div id='navcontainer'>\n";
        $html .= "<ul id='navlist'>\n";

        if (!is_array($list)) {
            $text = suxFunct::gtext();
            if (isset($text['navcontainer'])) $list = $text['navcontainer'];
        }


        if (is_array($list)) {

            // Make an educated guess as to which controller we are currently using?
            $compare = 'home';
            if (!empty($_GET['c'])) {
                $params = explode('/', $_GET['c']);
                $compare = array_shift($params);
            }

            if (!$GLOBALS['CONFIG']['CLEAN_URL']) $compare = "?c=$compare";
            else $compare = ltrim($GLOBALS['CONFIG']['URL'] . "/$compare", '/');

            // new dBug($compare);
            foreach ($list as $key => $val) {
                //new dBug($val);
                if ($compare && mb_strpos($val, $compare)) {
                    $html .= "<li><a href='{$val}' class='selected'>{$key}</a></li>\n";
                }
                else {
                    $html .= "<li><a href='{$val}'>{$key}</a></li>\n";
                }
            }

        }

        $html .= "</ul>\n";
        $html .= "</div>\n";
        $html .= "<div class='clearboth'></div>\n";

        return $html;

    }


    /**
    * Make URL Wrapper
    *
    * @param string $path controler value in /this/style
    * @param array $query http_build_query compatible array
    * @param bool $full return full url?
    * @return string url
    */
    function makeUrl($path, $query = null, $full = false) {

        $url = suxFunct::makeUrl($path, $query, $full);
        return htmlspecialchars($url); // Rendering HTML, fix it

    }


    /**
    * myHttpServer Wrapper
    *
    * @return string url
    */
    function myHttpServer() {

        return suxFunct::myHttpServer();

    }


    /**
    * TinyMCE Initialization
    *
    * @see http://tinymce.moxiecode.com/
    * @global string $CONFIG['URL']
    * @global string $CONFIG['PATH']
    * @global string $CONFIG['LANGUAGE']
    * @param string $init tinyMCE.init values
    * @return string the javascript code
    */
    function tinyMce($init) {

        // Remove trailing comma, if any
        $init = trim($init);
        $init = rtrim($init, ',');

        // TinyMCE Path
        $path = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/tinymce/jscripts/tiny_mce/tiny_mce.js';
        $path_css = $GLOBALS['CONFIG']['URL'] . '/media/' . $this->partition . '/css/tinymce.css';

        // TinyMCE Language
        if (!empty($_SESSION['language'])) $lang = $_SESSION['language'];
        else $lang = $GLOBALS['CONFIG']['LANGUAGE'];
        // Sanity check
        $test = $GLOBALS['CONFIG']['PATH'] . "/includes/symbionts/tinymce/jscripts/tiny_mce/langs/{$lang}.js";
        if (!is_file($test)) $lang = 'en'; // Revert back to english

        // Javascript
        $js = '<script type="text/javascript" src="' . $path . '"></script>
        <script language="javascript" type="text/javascript">
        // <![CDATA[

        tinyMCE.init({
            ' . $init . ',
            language : "' . $lang . '",
            content_css : "' . $path_css . '?" + new Date().getTime()
        });

        // ]]>
        </script>' . "\n";

        return $js;

    }


    /**
    * Copyright
    *
    * @return string html
    */
    function copyright() {

        $text = suxFunct::gtext();
        return $text['copyright'];

    }

    /**
    * Data License
    *
    * @return string html
    */
    function dataLicense() {

        $text = suxFunct::gtext();
        return $text['data_license'];

    }


}


// -------------------------------------------------------------------------
// Smarty {insert} functions
// -------------------------------------------------------------------------

/**
* Render userInfo
*
* @global string $CONFIG['URL']
* @global string $CONFIG['PARTITION']
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_userInfo($params) {

    unset($params); // Not used

    $text = suxFunct::gtext(); // Text

    $url = $GLOBALS['CONFIG']['URL'];
    $url_logout = suxFunct::makeUrl('/user/logout');

    if (!empty($_SESSION['partition'])) $partition = $_SESSION['partition'];
    else $partition = $GLOBALS['CONFIG']['PARTITION'];

    $tmp = '';
    $tmp .= '<div class="userinfo">' . "\n";

    if (!empty($_SESSION['nickname'])) {

        $u = new suxUser();
        if ($u->isRoot()) {
            $url_admin = suxFunct::makeUrl('/admin');
            $tmp .= "<span id='adminLink'>[ <a href='{$url_admin}'>{$text['admin']}</a> ]</span> ";
        }

        $url_profile = suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']);
        $tmp .= "<strong>{$text['welcome']}:</strong> <a href='{$url_profile}'>{$_SESSION['nickname']}</a> | ";
        $tmp .= "<a href='{$url_logout}'>{$text['logout']}</a> \n";

    }
    else {

        $url_login = suxFunct::makeUrl('/user/login');
        $url_login_openid = suxFunct::makeUrl('/user/login/openid');
        $url_register = suxFunct::makeUrl('/user/register');
        $url_register_openid = suxFunct::makeUrl('/user/register/openid');

        $tmp .= "<a href='{$url_login}'>{$text['login']}</a> ";
        $tmp .= "<a class='noBg' href='{$url_login_openid}'><img src='{$url}/media/{$partition}/assets/openid_icon.gif' alt='OpenID Login' class='openidLogin' /></a> | ";
        $tmp .= "<a href='{$url_register}'>{$text['register']}</a> ";
        $tmp .= "<a class='noBg' href='{$url_register_openid}'><img src='{$url}/media/{$partition}/assets/openid_icon.gif' alt='OpenID Login' class='openidLogin' /></a> \n";

    }

    $tmp .= '</div>' . "\n";

    return $tmp;

}


/**
* Highlight wrapper
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_highlight($params) {

    if (!isset($params['html'])) return false;
    $html = $params['html'];

    $search = null;
    if (isset($params['search'])) $search = $params['search'];

    return suxRenderer::highlight($html, $search);

}

?>