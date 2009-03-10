<?php

/**
* bookmarksRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');
require_once(dirname(__FILE__) . '/../bayes/bayesRenderer.php');


class bookmarksRenderer extends suxRenderer {


    // Objects
    private $bayesRenderer;
    private $user;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->user = new suxUser();
        $this->bayesRenderer = new bayesRenderer('bayes');

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

        $html = "<div class='tags'>{$this->gtext['tags']}: " . $html . '</div>';

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
        if ($tags) foreach ($tags as $key => $val) {
            $url = suxFunct::makeURL('/bookmarks/tag/' . $val['id']);
            $html .= "<a href='{$url}' style='font-size: {$val['size']}%;' class='tag'>{$key}</a> <span class='quantity' >({$val['quantity']})</span> ";
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
        plugins : "safari,paste,inlinepopups,autosave",
        width: "100%",
        height: 100,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "undo,redo,pastetext,pasteword,selectall,|,bold,italic,underline,strikethrough,|,link,unlink,|,cleanup,code",
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
    * @return string javascript
    */
    function genericBayesInterfaceInit() {

        return $this->bayesRenderer->genericBayesInterfaceInit();

    }


    /**
    * @param int $id messages id
    * @param string $link link table
    * @param string $module sux0r module, used to clear cache
    * @param string $document document to train
    * @return string html
    */
    function genericBayesInterface($id, $link, $module, $document) {

        return $this->bayesRenderer->genericBayesInterface($id, $link, $module, $document);

    }


    /**
    * @return string html
    */
    function isSubscribed($bookmark_id) {

        if (!$this->isLoggedIn())
            return  "<img src='{$this->url}/media/{$this->partition}/assets/sticky.gif' border='0' width='12' height='12' alt='' />";

        // Get config variables for template
        $tpl = new suxTemplate($this->module);
        $tpl->config_load('my.conf', $this->module);
        $image = $tpl->get_config_vars('imgUnsubscribed');

        // Don't query the database unnecessarily.
        static $img_cache = array();
        if (isset($img_cache[$bookmark_id])) {
            $image = $img_cache[$bookmark_id];
        }
        else {
            // If subscribed, change image
            $query = 'SELECT COUNT(*) FROM link_bookmarks_users WHERE bookmarks_id = ? AND users_id = ? ';
            $db = suxDB::get();
            $st = $db->prepare($query);
            $st->execute(array($bookmark_id, $_SESSION['users_id']));
            if ($st->fetchColumn() > 0) $image = $tpl->get_config_vars('imgSubscribed');
            $img_cache[$bookmark_id] = $image;
        }

        $html = "<img src='{$this->url}/media/{$this->partition}/assets/{$image}' border='0' width='12' height='12' alt=''
        onclick=\"toggleSubscription('{$bookmark_id}');\"
        style='cursor: pointer;'
        class='subscription{$bookmark_id}'
        />";

        return $html;

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
function insert_myBookmarksLink($params) {

    return suxFunct::makeUrl('/bookmarks/user/' . @$_SESSION['nickname']);

}


/**
* Render approve link between <li> tags
*
*/
function insert_bookmarksApproveLi($params) {

    if (!isset($_SESSION['users_id'])) return null;

    // Check that the user is allowed to edit
    $u = new suxUser();
    if (!$u->isRoot()) {
        $access = $u->getAccess('bookmarks');
        if ($access < $GLOBALS['CONFIG']['ACCESS']['bookmarks']['admin']) return null;
    }

    $query = "SELECT COUNT(*) FROM bookmarks WHERE draft = true  ";

    $db = suxDB::get();
    $st = $db->prepare($query);
    $st->execute();

    $count = $st->fetchColumn();
    $text = suxFunct::gtext('bookmarks');

    $url = suxFunct::makeUrl('/bookmarks/admin/');
    $html = "<li><a href='$url'>{$text['admin']}</a></li>\n";

    $url = suxFunct::makeUrl('/bookmarks/approve/');
    $html .= "<li><a href='$url'>{$text['approve_2']} ($count)</a></li>";

    return $html;

}

/**
* Render edit div
*
*/
function insert_bookmarksEdit($params) {

    if (!isset($_SESSION['users_id'])) return null;
    if (!isset($params['id'])) return null;

    // Cache
    static $allowed = null;
    if ($allowed === null) {
        $u = new suxUser();
        $allowed = true;
        if (!$u->isRoot()) {
            $access = $u->getAccess('bookmarks');
            if ($access < $GLOBALS['CONFIG']['ACCESS']['bookmarks']['admin']) $allowed = false;
        }
    }
    if (!$allowed) return null;

    $url = suxFunct::makeUrl('/bookmarks/edit/' . $params['id']);
    $text = suxFunct::gtext('bookmarks');

    $html = "<div class='edit'>[ <a href='$url'>{$text['edit']}</a> ]</div>";

    return $html;

}


?>