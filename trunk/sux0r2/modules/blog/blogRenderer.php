<?php

/**
* blogRenderer
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

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once(dirname(__FILE__) . '/../bayes/bayesRenderer.php');

class blogRenderer extends suxRenderer {

    // Objects
    private $user;
    private $msg;
    private $nb;
    private $link;
    private $bayesRenderer;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->user = new suxUser();
        $this->msg = new suxThreadedMessages();
        $this->nb = new bayesUser();
        $this->link = new suxLink();
        $this->bayesRenderer = new bayesRenderer('bayes');

    }


    /**
    * Return tags associated to this document
    *
    * @param int $id messages id
    * @return string html
    */
    function tags($id) {

        // ----------------------------------------------------------------
        // SQL
        // ----------------------------------------------------------------

        // Innerjoin query
        $innerjoin = '
        INNER JOIN link_messages_tags ON link_messages_tags.tags_id = tags.id
        ';

        // Select
        $query = "
        SELECT tags.id, tags.tag FROM tags
        {$innerjoin}
        WHERE link_messages_tags.messages_id = ?
        ";

        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($id));
        $cat = $st->fetchAll(PDO::FETCH_ASSOC);

        // ----------------------------------------------------------------
        // Html
        // ----------------------------------------------------------------

        foreach ($cat as $val) {
            $url = suxFunct::makeUrl('/blog/tag/' . $val['id']);
            $html .= "<a href='{$url}'>{$val['tag']}</a>, ";
        }

        if (!$html) return null; // No categories by trainer

        $html = rtrim($html, ', ');
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
        if ($tags) foreach ($tags as $key => $val) {
            $url = suxFunct::makeURL('/blog/tag/' . $val['id']);
            $html .= "<a href='{$url}' style='font-size: {$val['size']}%;' class='tag' >{$key}</a> <span class='quantity' >({$val['quantity']})</span> ";
        }
        return $html;

    }


    /**
    * Return bayes categories associated to this document by author
    *
    * @param int $id messages id
    * @param int $users_id users id (the author)
    * @return string html
    */
    function authorCategories($id, $users_id) {

        // ----------------------------------------------------------------
        // SQL
        // ----------------------------------------------------------------

        // Innerjoin query
        $innerjoin = '
        INNER JOIN bayes_auth ON bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
        INNER JOIN bayes_documents ON bayes_categories.id = bayes_documents.bayes_categories_id
        INNER JOIN link_bayes_messages ON link_bayes_messages.bayes_documents_id = bayes_documents.id
        INNER JOIN messages ON link_bayes_messages.messages_id = messages.id
        ';

        // Select, equivilant to nb->isCategoryTrainer()
        $query = "
        SELECT bayes_categories.category, bayes_categories.id FROM bayes_categories
        {$innerjoin}
        WHERE messages.id = ? AND bayes_auth.users_id = ? AND (bayes_auth.owner = true OR bayes_auth.trainer = true)
        ";

        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($id, $users_id));
        $cat = $st->fetchAll(PDO::FETCH_ASSOC);

        // ----------------------------------------------------------------
        // Html
        // ----------------------------------------------------------------

        foreach ($cat as $val) {
            $url = suxFunct::makeUrl('/blog/category/' . $val['id']);
            $html .= "<a href='{$url}'>{$val['category']}</a>, ";
        }

        if (!$html) return null; // No categories by trainer

        $html = rtrim($html, ', ');
        $html = "<p>{$this->gtext['bayes_categories']}: " . $html . '</p>';

        return $html;

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
    * Get {html_options} formated categories array
    *
    * @return array
    */
    function getUserCategories() {

        return $this->bayesRenderer->getUserCategories();

    }


    // ------------------------------------------------------------------------
    // Stuff like recent(), archives(), authors() is in the renderer because
    // there's no point in initializing if they aren't in the template
    // ------------------------------------------------------------------------


    /**
    *
    * @return array
    */
    function recent() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        $tmp = $this->msg->getRececentComments('blog');

        foreach($tmp as &$val) {
            $tmp2 = $this->user->getUser($val['users_id']);
            $val['nickname'] = $tmp2['nickname'];
            $tmp2 = $this->msg->getFirstPost($val['thread_id']);
            $val['title_fp'] = $tmp2['title'];
        }

        return $tmp;

    }


    /**
    *
    * @param int $limit sql limit value
    * @return array
    */
    function archives($limit = null) {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        $tmp = $this->msg->groupFirstPostsByMonths('blog', $limit);

        return $tmp;

    }


    /**
    *
    * @param int $limit sql limit value
    * @return array
    */
    function authors($limit = null) {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        $tmp = $this->msg->groupFirstPostsByUser('blog', $limit);
        foreach($tmp as &$val) {
            $u = $this->user->getUser($val['users_id']);
            $val['nickname'] = $u['nickname'];
        }

        return $tmp;


    }


    function indenter($level) {

        if ($level > 1) $level = $level * 10; // Level 1 is first comment
        if ($level > 100) $level = 100; // Prevent excessive threading
        return $level;

    }


    /**
    * TinyMCE Initialization for comments
    *
    * @see http://tinymce.moxiecode.com/
    * @return string the javascript code
    */
    function tinyMceComment() {

        $init = '
        mode : "textareas",
        theme : "advanced",
        editor_selector : "mceEditor",
        plugins : "safari,paste,inlinepopups,autosave",
        width: "100%",
        height: 200,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "undo,redo,pastetext,pasteword,selectall,|,bold,italic,underline,strikethrough,|,image,link,unlink,|,numlist,bullist,|,cleanup,code",
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
    * TinyMCE Initialization for bookmarks
    *
    * @see http://tinymce.moxiecode.com/
    * @return string the javascript code
    */
    function tinyMceBookmark() {

        $init = '
        mode : "textareas",
        theme : "advanced",
        editor_selector : "mceEditor",
        plugins : "safari,paste,inlinepopups,autosave",
        width: "100%",
        height: 100,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "undo,redo,pastetext,pasteword,selectall,|,bold,italic,underline,strikethrough,|,cleanup,code",
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
    * TinyMCE Initialization for blog editing
    *
    * @see http://tinymce.moxiecode.com/
    * @global string $CONFIG['URL']
    * @return string the javascript code
    */
    function tinyMceEditor() {

        $init = '
        mode : "textareas",
        theme : "advanced",
        editor_selector : "mceEditor",
        plugins : "safari,paste,media,table,fullscreen,inlinepopups,autosave,preview",
        width: "100%",
        height: 400,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "preview,|,undo,redo,pastetext,pasteword,selectall,|,bold,italic,underline,strikethrough,|,image,media,link,unlink,|,numlist,bullist,|,justifyleft,justifycenter,justifyright,justifyfull,outdent,indent,blockquote,|,table",
        theme_advanced_buttons2 : "forecolor,backcolor,formatselect,fontselect,fontsizeselect,|,removeformat,cleanup,code,fullscreen",
        theme_advanced_buttons3 : "",
        theme_advanced_statusbar_location : "bottom",
        entity_encoding : "raw",
        relative_urls : false,
        external_image_list_url : "' . $GLOBALS['CONFIG']['URL'] . '/modules/photos/getImagesByUser.php",
        ';
        return $this->tinyMce($init);

    }


    /**
    * @return array
    */
    function getUserVectors() {

        // Cache
        static $vectors = null;
        if (is_array($vectors)) return $vectors;
        $vectors = array();

        if (!isset($_SESSION['users_id'])) return $vectors ; // Anonymous user, skip

        foreach ($this->nb->getVectorsByUser($_SESSION['users_id']) as $key => $val) {
            $vectors[$key] = $val['vector'];
        }
        return $vectors;

    }



    /**
    * Used to populate dropdown(s) in suxEdit template
    *
    * @return array
    */
    function getTrainerVectors() {

        // Cache
        static $vectors = null;
        if (is_array($vectors)) return $vectors;
        $vectors = array();

        if ($GLOBALS['CONFIG']['FEATURE']['bayes'] == false) return $vectors; // Feature is turned off
        if (!isset($_SESSION['users_id'])) return $vectors ; // Anonymous user, skip

        foreach ($this->nb->getVectorsByTrainer($_SESSION['users_id']) as $key => $val) {
            $vectors[$key] = $val['vector'];
        }
        return $vectors;

    }


    /**
    * @return array
    */
    function getCategoriesByVector($vector_id) {

        $categories[''] = '---';
        foreach ($this->nb->getCategoriesByVector($vector_id) as $key => $val) {
            $categories[$key] = $val['category'];
        }
        return $categories;

    }



}

// -------------------------------------------------------------------------
// Smarty {insert} functions
// -------------------------------------------------------------------------


/**
* Render edit div
*
*/
function insert_edit($params) {

    if (!isset($_SESSION['users_id'])) return null;
    if (!isset($params['id'])) return null;

    // Cache
    static $allowed = null; // Admin permissions
    $allowed2 = true; // Publisher permissions
    if ($allowed == null) {
        // Check if a user is an administrator
        $u = new suxUser();
        $allowed = true;
        if (!$u->isRoot()) {
            $access = $u->getAccess('blog');
            if ($access < $GLOBALS['CONFIG']['ACCESS']['blog']['admin']) $allowed = false;
        }
    }
    if (!$allowed) {
        // Check if a user is the publisher of the message
        $m = new suxThreadedMessages();
        if ($access < $GLOBALS['CONFIG']['ACCESS']['blog']['publisher']) {
            $allowed = false;
            $allowed2 = false;
        }
        else {
            $tmp = $m->getMessage($params['id'], false);
            if ($tmp['users_id'] != $_SESSION['users_id']) $allowed2 = false;
        }
        if (!$allowed2) return null;
    }

    $url = suxFunct::makeUrl('/blog/edit/' . $params['id']);
    $text = suxFunct::gtext('blog');

    $html = "<div class='edit'>[ <a href='$url'>{$text['edit']}</a> ]</div>";

    return $html;

}


/**
* Render admin links wrapped in <li> tag
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_adminLi($params) {

    if (!isset($_SESSION['users_id'])) return null;

    // Check that the user is allowed to admin
    $u = new suxUser();
    $text = suxFunct::gtext('blog');
    $html = '';

    $is_root = $u->isRoot();
    $access = $u->getAccess('blog');

    if (!$is_root) {
        if ($access < $GLOBALS['CONFIG']['ACCESS']['blog']['publisher'])
            return null;
    }

    if ($is_root || $access >= $GLOBALS['CONFIG']['ACCESS']['blog']['admin']) {
        $url = suxFunct::makeUrl('/blog/admin');
        $html .= "<li><a href='{$url}'>{$text['admin']}</a></li>";
    }

    $url = suxFunct::makeUrl('/blog/edit/');
    $html .= "<li><a href='{$url}'>{$text['new']}</a></li>";

    return $html;

}



?>