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
    public $lang; // Language
    public $xhtml_header; // Full path to xhtml_header.tpl
    public $xhtml_footer; // Full path to xhtml_footer.tpl

    // Text
    public $url; // Site URL Prefix, e.g. /my/sux0r
    public $partition; // sux0r parition name
    public $title; // Variable to put between <title> tags
    public $stylesheets; // Variable to put stylesheets/text
    public $header; // Variable to put header/text
    public $nav_selected; // Selected key for $nav array

    // Arrays
    public $nav = array(); // Variable to keep navlist
    public $text  = array(); // Variable to keep body/text
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

        $this->module = $module; // Module

        // Language
        if (!empty($_SESSION['language'])) $this->lang = $_SESSION['language'];
        else $this->lang = $GLOBALS['CONFIG']['LANGUAGE'];

        // Path to XTHML header & footer templates
        $this->xhtml_header = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $GLOBALS['CONFIG']['PARTITION'] . '/xhtml_header.tpl';
        if (!file_exists($this->xhtml_header)) $this->xhtml_header = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/xhtml_header.tpl';
        $this->xhtml_footer = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $GLOBALS['CONFIG']['PARTITION'] . '/xhtml_footer.tpl';
        if (!file_exists($this->xhtml_footer)) $this->xhtml_footer = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/xhtml_footer.tpl';

        // Defaults
        $this->url = $GLOBALS['CONFIG']['URL'];
        $this->partition = $GLOBALS['CONFIG']['PARTITION'];
        $this->title = $GLOBALS['CONFIG']['TITLE'];
        $this->bool['analytics'] = true;

        // Stylesheets
        $this->stylesheets = "<link rel='stylesheet' type='text/css' href='{$this->url}/media/{$this->partition}/css/base.css' />\n";
        if (file_exists($GLOBALS['CONFIG']['PATH'] . "/media/{$this->partition}/css/{$this->module}.css")) {
            $this->stylesheets .= "<link rel='stylesheet' type='text/css' href='{$this->url}/media/{$this->partition}/css/{$this->module}.css' />\n";
        }

    }


    /**
    * Assign
    *
    * @param string $variable the public variable to work with
    * @param string $value content
    * @param string $key key or append
    */
    function assign($variable, $value, $key = false) {

        // Array
        if (is_array($this->$variable)) {
            if (!$key) return;
            else {
                $this->$variable[$key] = $value;
                return;
            }
        }

        // Text
        if ($key) $this->$variable .= $value; // Append
        else $this->$variable = $value;

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

        $tpl->assign('title', $title);
        $tpl->assign('image', $image);
        $tpl->assign('caption', $caption);
        $tpl->assign('width', $width);
        $tpl->assign('content', $content);

        $path = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $GLOBALS['CONFIG']['PARTITION'];
        if (!file_exists("$path/widget.tpl")) $path = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r';
        return $tpl->fetch("file:$path/widget.tpl");

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

        return suxFunct::makeUrl($path, $query, $full);

    }


    // -------------------------------------------------------------------------
    // Javascript
    // -------------------------------------------------------------------------


    /**
    * TinyMCE Initialization
    *
    * @see http://tinymce.moxiecode.com/
    * @global string $CONFIG['URL']
    * @param int $width optional width parameter for editor window
    * @param int $height optional height parameter for editor window
    * @return string the javascript code
    */
    function tinyMceInit($width = 640, $height = 400) {

        $path = $GLOBALS['CONFIG']['URL'] . '/symbtions/tinymce/jscripts/tiny_mce/tiny_mce.js';

        $js = '<script type="text/javascript" src="' . $path . '"></script>
        <script language="javascript" type="text/javascript">
        // <![CDATA[

        tinyMCE.init({
            mode : "textareas",
            theme : "advanced",
            editor_selector : "mceEditor",
            plugins : "paste,media,table,fullscreen,layer,safari",
            width: ' . $width . ',
            height: ' . $height . ',
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_buttons1 : "bold,italic,underline,justifyleft,justifycenter,justifyright,justifyfull,numlist,bullist,outdent,indent,forecolor,backcolor,fontselect,fontsizeselect",
            theme_advanced_buttons2 : "undo,redo,pastetext,pasteword,link,unlink,table,image,media,removeformat,cleanup,code,fullscreen",
            theme_advanced_buttons3 : "",
            entity_encoding : "raw",
            relative_urls : false
        });

        // ]]>
        </script>';
        $js .= "\n";

        return $js;
    }


    /**
    * Cropper Initialization
    *
    * @see http://www.defusion.org.uk/code/javascript-image-cropper-ui-using-prototype-scriptaculous/
    * @global string $CONFIG['URL']
    * @param int $x ratio width
    * @param int $y ratio height
    * @return string the javascript code
    */
    function cropperInit($x, $y) {

        global $CONFIG;

        $js = '
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/symbionts/scriptaculous/lib/prototype.js"></script>
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/symbionts/scriptaculous/src/scriptaculous.js"></script>
        <script type="text/javascript" src="' . $GLOBALS['CONFIG']['URL'] . '/symbionts/cropper/cropper.js"></script>
        <script type="text/javascript" language="javascript">
        // <![CDATA[

                function onEndCrop( coords, dimensions ) {
                    $( "x1" ).value = coords.x1;
                    $( "y1" ).value = coords.y1;
                    $( "width" ).value = dimensions.width;
                    $( "height" ).value = dimensions.height;
                }

                Event.observe( window, "load", function() {
                    new Cropper.Img(
                        "cropperImage",
                        {
                            ratioDim: {
                                x: ' . $x . ',
                                y: ' . $y . '
                            },
                            displayOnInit: true,
                            onEndCrop: onEndCrop
                        }
                    );
                } );

        // ]]>
        </script>
        ';

        return $js;

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

?>