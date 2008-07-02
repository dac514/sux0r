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
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once(dirname(__FILE__) . '/../bayes/bayesRenderer.php');

class blogRenderer extends suxRenderer {

    // Arrays
    public $fp = array(); // Array of first posts
    public $sidelist = array(); // Array of threads in sidebar

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
        WHERE messages.id = ? AND bayes_auth.users_id = ? AND (bayes_auth.owner = 1 OR bayes_auth.trainer = 1)
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
        $html = "<p>Author's Categories: " . $html . '</p>';

        return $html;

    }



    /**
    * @param int $id messages id
    * @param string $link link table
    * @param string $document document to train
    * @return string html
    */
    function userCategories($id, $link, $document) {

        return $this->bayesRenderer->genericBayesInterface($id, $link, $document);

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
        if ($level > 100) $level = 100; // Prevent excecive threading
        return "{$level}px";

    }


    // ------------------------------------------------------------------------
    // suxEdit
    // ------------------------------------------------------------------------

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
    function tinyMceInit() {

        // TinyMCE Path
        $path = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/tinymce/jscripts/tiny_mce/tiny_mce.js';

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
            mode : "textareas",
            theme : "advanced",
            editor_selector : "mceEditor",
            plugins : "paste,media,table,fullscreen,inlinepopups,autosave,safari",
            width: "100%",
            height: 400,
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_buttons1 : "bold,italic,underline,justifyleft,justifycenter,justifyright,justifyfull,numlist,bullist,outdent,indent,forecolor,backcolor,fontselect,fontsizeselect",
            theme_advanced_buttons2 : "undo,redo,pastetext,pasteword,selectall,link,unlink,table,image,media,removeformat,cleanup,code,fullscreen",
            theme_advanced_buttons3 : "",
            theme_advanced_statusbar_location : "bottom",
            entity_encoding : "raw",
            language : "' . $lang . '",
            relative_urls : false
        });

        // ]]>
        </script>' . "\n";

        return $js;

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


?>