<?php

/**
* suxRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

/*
A renderer object acts as a bridge, and is generally passed to a template
object.

Example:
$r = new suxRenderer('module');
$smarty->assignByRef('r', $r);

PHP pages are procedural in the sense that a page will be rendered once, from
top to bottom, then PHP will exit. Additionally, template rendering happens
after everything else, so the state of the application doesn't change while
rendering. Knowing this, a renderer  object can do interesting things, such as
cache the return values of functions, to allow for smarter interactions between
Templates and the rest of the application.

See:
http://www.phpinsider.com/smarty-forum/viewtopic.php?t=12683
*/

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

        // Defaults
        $this->url = $GLOBALS['CONFIG']['URL'];
        $this->title = $GLOBALS['CONFIG']['TITLE'];
        $this->sitename = $GLOBALS['CONFIG']['TITLE'];
        $this->bool['analytics'] = false;

        $this->gtext = suxFunct::gtext($module); // Gtext
        $this->module = $module; // Module

        // Partition
        if (!empty($_SESSION['partition'])) $this->partition = $_SESSION['partition'];
        else $this->partition  = $GLOBALS['CONFIG']['PARTITION'];

        // Path to XTHML header & footer templates
        $this->xhtml_header = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $this->partition  . '/globals/xhtml_header.tpl';
        if (!file_exists($this->xhtml_header)) $this->xhtml_header = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/globals/xhtml_header.tpl';
        $this->xhtml_footer = $GLOBALS['CONFIG']['PATH'] . '/templates/' . $this->partition  . '/globals/xhtml_footer.tpl';
        if (!file_exists($this->xhtml_footer)) $this->xhtml_footer = $GLOBALS['CONFIG']['PATH'] . '/templates/sux0r/globals/xhtml_footer.tpl';

        // Stylesheets
        $this->stylesheets = "<link rel='stylesheet' type='text/css' href='{$this->url}/media/{$this->partition}/css/base.css' />\n";
        if (file_exists($GLOBALS['CONFIG']['PATH'] . "/media/{$this->partition}/css/{$this->module}.css")) {
            $this->stylesheets .= "<link rel='stylesheet' type='text/css' href='{$this->url}/media/{$this->partition}/css/{$this->module}.css' />\n";
        }

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
    * Return a unique id
    *
    * @return string random md5
    */
    function uniqueId() {

        return md5(uniqid(time()));

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


        $r = [];
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
            if (!preg_match('/\.(jpe?g|gif|png)$/i', (string) $image)) $image = null;

        }
        if ($url2) {
            $url2 = suxFunct::canonicalizeUrl($url2);
            if (!filter_var($url2, FILTER_VALIDATE_URL)) $url2 = null;
        }

        // Image manipulation
        $size = ($image) ? @getimagesize($image) : null;
        if ($size) $image = "<img src='$image' alt='' {$size[3]} />";
        else $image = null;


        // Makeshift renderer object
        $r['arr']['size'] = $size;
        $r['text']['title'] = $title;
        $r['text']['image'] = $image;
        $r['text']['caption'] = $caption;
        $r['text']['content'] = $content;
        $r['text']['floater'] = $floater;
        $r['text']['url_title'] = $url;
        $r['text']['url_image'] = $url;
        if ($url2) $r['text']['url_image'] = $url2;
        $r = (object) $r;

        // Template
        $tpl = new suxTemplate('globals');
        $tpl->assignByRef('r', $r);


        return $tpl->fetch('widget.tpl');

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

        $ldelim = '&*(!@';
        $rdelim = '$%^)~';

        $replacements = array();
        foreach($words as $word) {
            $replacements[] = $ldelim . $word . $rdelim;
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

        $html = str_replace($ldelim, "<span class='highlight'>", $html);
        $html = str_replace($rdelim, '</span>', $html);

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
    static function navlist($list = null) {

        $r = [];
        if (!is_array($list)) {
            $gtext = suxFunct::gtext();
            if (isset($gtext['navcontainer'])) $list = $gtext['navcontainer'];
        }

        if (is_array($list)) {
            // Make an educated guess as to which controller we are currently using?
            $compare = 'home';
            if (!empty($_GET['c'])) {
                $params = explode('/', (string) $_GET['c']);
                $compare = array_shift($params);
            }

            if (!$GLOBALS['CONFIG']['CLEAN_URL']) $compare = "?c=$compare";
            else $compare = ltrim($GLOBALS['CONFIG']['URL'] . "/$compare", '/');

            $selected = null;
            if ($compare) {
                foreach ($list as $key => $val) {
                    if (is_array($val) && mb_strpos($val[0], $compare)) { // Sub-menu
                        $selected = $key;
                        break;
                    }
                    elseif (is_string($val) && mb_strpos($val, $compare)) { // No sub-menu
                        $selected = $key;
                        break;
                    }
                }
            }
        }

        // Makeshift renderer object
        $r['arr']['list'] = $list;
        $r['text']['selected'] = $selected;
        $r = (object) $r;

        // Template
        $tpl = new suxTemplate('globals');
        $tpl->assignByRef('r', $r);

        return $tpl->fetch('navlist.tpl');

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
    function tinyMceInit($init) {

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
        $js = '
        <script type="text/javascript" src="' . $path . '"></script>
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
    * jQuery Initialization
    *
    * @global string $CONFIG['URL']
    * @param bool $ui include jQuery-Ui component.
    * @return string html header code
    */
    function jQueryInit($ui = true) {
        // jQuery
        $tmp1 = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/jquery/js/jquery-1.4.4.min.js';
        // jQuery UI
        $tmp2 = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/jquery/css/smoothness/jquery-ui-1.8.9.custom.css';
        $tmp3 = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/jquery/js/jquery-ui-1.8.9.custom.min.js';

        $js = '<script type="text/javascript" src="' . $tmp1 . '"></script>' . "\n";
        if ($ui) {
            $js .= '<link rel="stylesheet" type="text/css" href="' . $tmp2 . '" />' . "\n";
            $js .= '<script type="text/javascript" src="' . $tmp3 . '"></script>' . "\n";
        }
        return $js;
    }


    /**
    * Copyright
    *
    * @return string html
    */
    function copyright() {

        $gtext = suxFunct::gtext();
        return $gtext['copyright'];

    }

    /**
    * Data License
    *
    * @return string html
    */
    function dataLicense() {

        $gtext = suxFunct::gtext();
        return $gtext['data_license'];

    }


}


// -------------------------------------------------------------------------
// Smarty {insert} functions
// -------------------------------------------------------------------------

/**
* Render navlist
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_navlist($params) {

    unset($params); // Not used

    return suxRenderer::navlist();

}


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

    $tpl = new suxTemplate('globals');
    $r = new suxRenderer('globals'); // Renderer
    $tpl->assignByRef('r', $r); // Renderer referenced in template


    if (!empty($_SESSION['nickname'])) {

        $u = new suxUser();
        if ($u->isRoot()) $r->bool['root'] = true;
        $r->text['nickname'] = $_SESSION['nickname'];

        return $tpl->fetch('userinfo.tpl');

    }
    else {

        return $tpl->fetch('userlogin.tpl');

    }

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

