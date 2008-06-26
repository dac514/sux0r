<?php

/**
* renderer
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
require_once(dirname(__FILE__) . '/../bayes/suxNbUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');


class renderer extends suxRenderer {

    // Arrays
    public $fp = array(); // Array of first posts
    public $sidelist = array(); // Array of threads in sidebar

    // Objects
    private $user;
    private $msg;
    private $nb;
    private $link;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->user = new suxUser();
        $this->msg = new suxThreadedMessages();
        $this->nb = new suxNbUser();
        $this->link = new suxLink();

    }


    /**
    * Return bayes categories associated to this document by author
    *
    * @param int $id messages id
    * @param int $users_id users id (the author)
    * @return string html
    */
    function authorCategories($id, $users_id) {

        $links = $this->link->getLinks('link_bayes_messages', 'messages', $id);
        if (!$links) return null; // No linked bayes_documents

        $html = '';
        foreach($links as $val) {
            // $val is a bayes_documents id
            $cat = $this->nb->getCategoriesByDocument($val);
            foreach ($cat as $key => $val2) {
                // $cat is a category
                // $key is the bayes_categories id,
                // $val2 is an array of category info
                if ($this->nb->isCategoryTrainer($key, $users_id)) {
                    // This author is the category trainer,
                    // They (or someone they share with) have already assigned a category
                    $url = suxFunct::makeUrl('/blog/category/' . $key);
                    $html .= "<a href='{$url}'>{$val2['category']}</a>, ";
                }
            }
        }
        if (!$html) return null; // No categories by trainer

        $html = rtrim($html, ', ');
        $html = "<p>Author's Categories: " . $html . '</p>';

        return $html;

    }



    /**
    * @param int $id messages id
    * @param int $users_id users id
    * @param string $document document to train
    * @return string html
    */
    function userCategories($id, $document) {

        if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

        /* Get a list of all the vectors/categories the user has access to */

        $vectors = $this->getUserVectors();
        if (!$vectors) return null; // No user vectors, skip

        // Cache
        static $v_trainer = null;
        static $v_user = null;
        if (!is_array($v_trainer) || !is_array($v_user)) {

            /* Split the vectors into those the user can train, and those he/she can't */

            $v_trainer = array();
            $v_user = array();

            foreach ($vectors as $key => $val) {
                if ($this->nb->isVectorTrainer($key, $_SESSION['users_id'])) {
                    $v_trainer[$key] = array(
                        'vector' => $val,
                        'categories' => $this->nb->getCategoriesByVector($key),
                        );
                }
                else {
                    $v_user[$key] = array(
                        'vector' => $val,
                        'categories' => $this->nb->getCategoriesByVector($key),
                        );
                }
            }
            unset($vectors); // No longer used

        }

        /* Get all the bayes categories linked to the document id that the user has access to */

        $categories = array();
        $links = $this->link->getLinks('link_bayes_messages', 'messages', $id);
        if ($links) {
            foreach($links as $val) {
                // $val is a bayes_documents id
                $cat = $this->nb->getCategoriesByDocument($val);
                foreach ($cat as $key => $val2) {
                    // $cat is a category
                    // $key is the bayes_categories id,
                    // $val2 is an array of category info
                    if ($this->nb->isCategoryUser($key, $_SESSION['users_id'])) {
                        // Category user, someone trained the document and this
                        // user has access to that information
                        $categories[$key] = $val2;
                    }
                }
            }
        }


        $html = '';
        $i = 0; // Used to identify ajax trainable vector

        foreach(array($v_trainer, $v_user) as $vectors) {

            if (count($vectors)) {
                foreach ($vectors as $key => $val) {

                    // Vector name to be replaced
                    $uniqid = time() . substr(md5(microtime()), 0, rand(5, 12));
                    $html .= "@_{$uniqid}_@ : ";

                    if ($i == 0) {
                        // TODO: Can submit with AJAX
                        $html .= '<select name="category_id[]" class="revert">';
                    }
                    else {
                        // Looks pretty, does nothing
                        $html .= '<select name="null" class="revert">';
                    }

                    $html .= '<option label="---" value="">---</option>';


                    /* Check if the vector is categorized */

                    $is_categorized = false;
                    foreach ($val['categories'] as $key2 => $val2) {
                        if (array_key_exists($key2, $categories)) {
                            $is_categorized = $key2;
                            break;
                        }
                    }

                    if ($is_categorized === false) {

                        /* Not categorized, get bayesian scores */

                        $replace = $val['vector'];
                        $html = str_replace("@_{$uniqid}_@", $replace, $html);

                        $j = 0;
                        $scores = $this->nb->categorize($document, $key);
                        foreach ($scores as $key2 => $val2) {
                            $tmp = $val2['category'] . ' (' . round($val2['score'] * 100, 2) . ' %)';
                            $html .= '<option label="' . $tmp . '" value="' . $key2 . '" ';
                            if ($j == 0) $html .= 'selected="selected" ';
                            $html .= '>' . $tmp . '</option>';
                            ++$j;
                        }
                    }
                    else {

                        /* Is already categorized, don't calculate */

                        $replace = "<span style='color:green;font-weight:bold;'>{$val['vector']}</span>";
                        $html = str_replace("@_{$uniqid}_@", $replace, $html);

                        foreach ($val['categories'] as $key2 => $val2) {

                            $html .= '<option label="' . $val2['category'] . '" value="' . $key2 . '" ';
                            if ($is_categorized == $key2) $html .= 'selected="selected" ';
                            $html .= '>' . $val2['category'] . '</option>';

                        }

                    }

                    $html .= '</select><br />' . "\n";

                }
            }

            ++$i; // Used to identify ajax trainable vector.

        }


        return $html;

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