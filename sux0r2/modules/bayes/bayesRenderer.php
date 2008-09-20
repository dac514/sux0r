<?php

/**
* bayesRenderer
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
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');
require_once('bayesUser.php');

class bayesRenderer extends suxRenderer {

    // Arrays
    public $gtext = array();

    // Objects
    private $nb;
    private $link;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->gtext = suxFunct::gtext('bayes'); // Language
        $this->nb = new bayesUser();
        $this->link = new suxLink();

    }


    /**
    * @global string $CONFIG['URL']
    * @param bool $init include .js files?
    * @return string the javascript code
    */
    function genericBayesInterfaceInit($init = true) {

        if (!isset($_SESSION['users_id'])) return null; // Skip anonymous users

        $js = '';

        if ($init) {
            $path_prototype = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/scriptaculous/lib/prototype.js';
            $path_scriptaculous = $GLOBALS['CONFIG']['URL'] . '/includes/symbionts/scriptaculous/src/scriptaculous.js';
            $js .= '<script type="text/javascript" src="' . $path_prototype . '"></script>' . "\n";
            $js .= '<script type="text/javascript" src="' . $path_scriptaculous . '"></script>' . "\n";
        }

        $js .="
        <script type='text/javascript'>
        // <![CDATA[

        function suxTrain(placeholder, link, module, id, cat_id) {
            var url = '{$GLOBALS['CONFIG']['URL']}/modules/bayes/train.php';
            var pars = { link: link, module: module, id: id, cat_id: cat_id };
            new Effect.Highlight($(placeholder));
            new Ajax.Request(url, {
                method: 'post',
                parameters: pars,
                onSuccess: function() {
                    $(placeholder).addClassName('nbVecTrained');
                    Effect.Pulsate($(placeholder));
                },
                onFailure: function(transport){
                    if (transport.responseText.strip())
                        alert(transport.responseText);
                }
            });
        }

        function suxNotTrainer(placeholder) {
            Effect.Shake($(placeholder));
        }

        // ]]>
        </script>
        ";

        return $js;

    }


    /**
    * @param int $id id
    * @param string $link link table
    * @param string $module sux0r module, used to clear cache
    * @param string $document document to train
    * @return string html
    */
    function genericBayesInterface($id, $link, $module, $document) {

        /* Get a list of all the vectors/categories the user has access to */

        // Cache
        static $vectors = null;
        if (!is_array($vectors)) {
            $vectors = array();
            if (isset($_SESSION['users_id'])) foreach ($this->nb->getSharedVectors($_SESSION['users_id']) as $key => $val) {
                $vectors[$key] = $val;
            }
        }
        if (!count($vectors)) return null; // No user vectors, skip

        // Cache
        static $v_trainer = null;
        static $v_user = null;
        if (!is_array($v_trainer) || !is_array($v_user)) {

            /* Split the vectors into those the user can train, and those he/she can't */

            $v_trainer = array();
            $v_user = array();

            foreach ($vectors as $key => $val) {

                if ($val['owner'] || $val['trainer']) {
                    $v_trainer[$key] = array(
                        'vector' => $val['vector'],
                        'categories' => $this->nb->getCategoriesByVector($key),
                        );
                }
                else {
                    $v_user[$key] = array(
                        'vector' => $val['vector'],
                        'categories' => $this->nb->getCategoriesByVector($key),
                        );
                }

            }
        }

        /* Get all the bayes categories linked to the document id that the user has access to */

        $link_table = $this->link->getLinkTableName($link, 'bayes');
        $link_table2 = $this->link->getLinkColumnName($link_table, $link);
        $innerjoin = "
        INNER JOIN bayes_auth ON bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
        INNER JOIN bayes_documents ON bayes_categories.id = bayes_documents.bayes_categories_id
        INNER JOIN {$link_table} ON {$link_table}.bayes_documents_id = bayes_documents.id
        INNER JOIN {$link_table2} ON {$link_table}.{$link_table2}_id = {$link_table2}.id
        ";

        $query = "
        SELECT bayes_categories.id FROM bayes_categories
        {$innerjoin}
        WHERE {$link_table2}.id = ? AND bayes_auth.users_id = ?
        "; // Note: bayes_auth WHERE condition equivilant to nb->isCategoryUser()

        $db = suxDB::get();
        $st = $db->prepare($query);
        $st->execute(array($id, $_SESSION['users_id']));
        $tmp = $st->fetchAll(PDO::FETCH_ASSOC);

        $categories = array();
        foreach ($tmp as $key => $val) {
            $categories[$val['id']] = true;
        }


        /* Begin rendering */

        $html = "<div class='nbInterface'>\n";
        $i = 0; // Used to identify $v_trainer[]
        foreach(array($v_trainer, $v_user) as $vectors2) {

            foreach ($vectors2 as $key => $val) {

                if (count($val['categories']) < 2) continue; // Not enough categories, skip

                // Vector name to be replaced
                $uniqid = time() . substr(md5(microtime()), 0, rand(5, 12));
                $html .= "<span id='nb{$uniqid}'>@_{$uniqid}_@</span>";

                if ($i == 0) {
                    // this is $v_trainer[], Ajax trainable
                    $html .= '<select name="category_id[]" class="nbCatDropdown" ';
                    $html .= "onchange=\"suxTrain('nb{$uniqid}', '{$link}', '{$module}', {$id}, this.options[selectedIndex].value);\" ";
                    $html .= '>';

                }
                else {
                    // this is $v_user[], sit pretty, do nothing
                    $html .= '<select name="null" class="nbCatDropdown" ';
                    $html .= "onchange=\"suxNotTrainer('nb{$uniqid}');\" ";
                    $html .= '>';

                }

                /* Check if the vector is categorized */

                $is_categorized = false;
                foreach ($val['categories'] as $key2 => $val2) {
                    if (isset($categories[$key2])) {
                        $is_categorized = $key2;
                        break;
                    }
                }


                /* Get bayesian scores */

                if ($is_categorized) $replace = "<span class='nbVecTrained'>{$val['vector']} : </span>";
                else $replace = $val['vector'] . ' : ';
                $html = str_replace("@_{$uniqid}_@", $replace, $html);

                $j = 0;
                $scores = $this->nb->categorize($document, $key);
                foreach ($scores as $key2 => $val2) {
                    $tmp = $val2['category'] . ' (' . round($val2['score'] * 100, 2) . ' %)';
                    $html .= '<option label="' . $tmp . '" value="' . $key2 . '" ';
                    if ($is_categorized == $key2 || $j == 0) $html .= 'selected="selected" ';
                    $html .= '>' . $tmp . '</option>';
                    ++$j;
                }

                $html .= '</select>' . "\n";

            }

            ++$i; // Used to identify $v_trainer[]

        }

        $html .= "</div>\n";

        return $html;

    }


    /**
    * Get {html_options} formated vectors array
    *
    * @return array
    */
    function getUserOwnedVectors() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        foreach ($this->getVectorsByOwnerArray() as $key => $val) {
            if (!in_array($val['vector'], $tmp)) $tmp[$key] = $val['vector'];
            else $tmp[$key] = "{$val['vector']} (id:$key)";
        }

        return $tmp;

    }


    /**
    * Get {html_options} formated vectors array
    *
    * @return array
    */
    function getUserSharedVectors() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        foreach ($this->getSharedVectorsArray() as $key => $val) {
            if (!in_array($val['vector'], $tmp)) $tmp[$key] = $val['vector'];
            else $tmp[$key] = "{$val['vector']} (id:$key)";
        }

        return $tmp;

    }


    /**
    * Get {html_options} formated categories array
    *
    * @return array
    */
    function getUserOwnedCategories() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        foreach ($this->getVectorsByOwnerArray() as $key => $val) {

            // Create a dropdown with <optgroup> array
            $x = "{$val['vector']}";
            if (isset($tmp[$x])) $x = "{$val['vector']} (id:$key)"; // Duplicate vector name, append id to avoid confusion
            $y = array();
            foreach ($this->nb->getCategoriesByVector($key) as $key2 => $val2) {
                $y[$key2] = "{$val2['category']}";
            }

            $tmp[$x] = $y;
        }

        return $tmp;

    }


    /**
    * Get {html_options} formated categories array
    *
    * @return array
    */
    function getUserTrainableCategories() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        foreach ($this->getVectorsByTrainerArray() as $key => $val) {

            // Create a dropdown with <optgroup> array
            $x = "{$val['vector']}";
            if (isset($tmp[$x])) $x = "{$val['vector']} (id:$key)";
            $y = array();
            foreach ($this->nb->getCategoriesByVector($key) as $key2 => $val2) {
                $y[$key2] = "{$val2['category']}";
            }

            $tmp[$x] = $y;
        }

        return $tmp;

    }


    /**
    * Get {html_options} formated categories array
    *
    * @return array
    */
    function getUserCategories() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

        foreach ($this->getVectorsByUserArray() as $key => $val) {

            // Create a dropdown with <optgroup> array
            $x = "{$val['vector']}";
            if (isset($tmp[$x])) $x = "{$val['vector']} (id:$key)";
            $y = array();
            foreach ($this->nb->getCategoriesByVector($key) as $key2 => $val2) {
                $y[$key2] = "{$val2['category']}";
            }

            $tmp[$x] = $y;
        }

        return $tmp;

    }


    /**
    * Get documents
    *
    * @return array
    */
    function getUserOwnedDocuments() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        foreach ($this->getVectorsByOwnerArray() as $key => $val) {
            foreach ($this->nb->getDocumentsByVector($key) as $key2 => $val2) {
                $category = $this->nb->getCategory($val2['category_id']);
                $tmp[$key2] = "{$key2} - {$val['vector']}, {$category['category']}";
            }
        }

        return $tmp;

    }


    /**
    * Get category stats
    *
    * @return string html formated stats
    */
    function getCategoryStats() {

        static $html = null;
        if ($html) return $html; // Cache

        $text =& $this->gtext;
        $cat = 0;
        $html = "<div id='bStats'><ul>\n";
        foreach ($this->getSharedVectorsArray() as $key => $val) {
            $html .= "<li class='bStatsVec'>{$val['vector']}";
            if (!$this->nb->isVectorOwner($key, $_SESSION['users_id'])) $html .= ' <em>(' . $text['shared'] . ')/em>';
            $html .= ":</li>\n<ul>\n";
            foreach ($this->nb->getCategoriesByVector($key) as $key2 => $val2) {
                $doc_count = $this->nb->getDocumentCountByCategory($key2);
                $html .= "<li class='bStatsCat'>{$val2['category']}:</li>";
                $html .= "<ul>\n";
                $html .= "<li class='bStatsDoc'>{$text['documents']}: $doc_count</li><li class='bStatsTok'>{$text['tokens']}: {$val2['token_count']}</li>\n";
                $html .= "</ul>\n";
                ++$cat;
            }
            $html .= "</ul>\n";
        }
        $html .= "</ul></div>\n";

        if (!$cat) return null;
        else return $html;
    }


    /**
    * @return string html table
    */
    function getShareTable() {

        static $html = null;
        if ($html) return $html; // Cache

        $text =& $this->gtext;
        $html .= "<table class='shared'><thead><tr>
        <th>{$text['vector']}</th>
        <th>{$text['user']}</th>
        <th>{$text['trainer']}</th>
        <th>{$text['owner']}</th>
        <th>{$text['unshare']}</th>
        </tr></thead><tbody>\n";

        $user = new suxUser();

        // Owned, and the users shared with
        $vectors = $this->getVectorsByOwnerArray();
        foreach ($vectors as $key => $val) {


            $html .= "<tr class='mine'>
            <td>{$val['vector']}</td>
            <td>{$_SESSION['nickname']}</td>
            <td>x</td>
            <td>x</td>
            <td><em>n/a</em></td>
            </tr>\n";

            $shared = $this->nb->getVectorAuthorization($key);
            foreach ($shared as $val2) {

                if ($val2['users_id'] == $_SESSION['users_id']) continue;

                $u = $user->getUser($val2['users_id']);

                $trainer = $val2['trainer'] ? 'x' : null;

                $owner = null;
                if ($val2['owner']) {
                    $trainer = 'x'; // Training is implied
                    $owner = 'x';
                }


                $html .= "<tr>
                <td>{$val['vector']}</td>
                <td>{$u['nickname']}</td>
                <td>{$trainer}</td>
                <td>{$owner}</td>
                <td><input type='checkbox' name='unshare[][$key]' value='{$val2['users_id']}' /></td>
                </tr>\n";

            }


        }

        // Shared, but not owned
        $vectors = $this->getSharedVectorsArray();
        foreach ($vectors as $key => $val) {

            if ($val['owner']) continue;

            $trainer = $val['trainer'] ? 'x' : null;

            // TODO:
            // Ajax tooltip on vector -> getOwners.php

            $html .= "<tr class='mineToo'>
            <td>{$val['vector']}</td>
            <td>{$_SESSION['nickname']}</td>
            <td>{$trainer}</td>
            <td></td>
            <td><input type='checkbox' name='unshare[][$key]' value='{$_SESSION['users_id']}' /></td>
            </tr>\n";

        }

        $html .= "</tbody></table>\n";

        return $html;

    }

    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    private function getVectorsByOwnerArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getVectorsByOwner($_SESSION['users_id']);

    }


    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    private function getVectorsByTrainerArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getVectorsByTrainer($_SESSION['users_id']);

    }


    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    private function getVectorsByUserArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getVectorsByUser($_SESSION['users_id']);

    }


    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    private function getSharedVectorsArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getSharedVectors($_SESSION['users_id']);

    }



}


// -------------------------------------------------------------------------
// Smarty {insert} functions
// -------------------------------------------------------------------------

/**
* Render bayesFilters
*
* @param array $params smarty {insert} parameters
* @return string html
*/
function insert_bayesFilters($params) {

    if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

    $r = new bayesRenderer('bayes'); // Renderer
    if (!$r->getUserCategories()) return null; // No categories, skip

    $tpl = new suxTemplate('bayes'); // Template
    $r->text = suxFunct::gtext('bayes'); // Language

    if (isset($_GET['filter'])) $tpl->assign('filter', $_GET['filter']);
    if (isset($_GET['threshold']) && $_GET['threshold'] !== false) $tpl->assign('threshold', $_GET['threshold']);
    if (isset($_GET['search'])) $tpl->assign('search', $_GET['search']);
    if (isset($params['form_url'])) $r->text['form_url'] = $params['form_url'];
    if (isset($params['hidden']) && is_array($params['hidden'])) $r->text['hidden'] = $params['hidden'];

    $tpl->assign_by_ref('r', $r);
    return $tpl->fetch('filters.tpl');

}


/**
* Render bayesFilterScript
*
* @return string javascript
*/
function insert_bayesFilterScript() {

    if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

    $r = new bayesRenderer('bayes'); // Renderer
    if (!$r->getUserCategories()) return null; // No categories, skip

    $threshold = 0;
    if (isset($_GET['threshold']) && $_GET['threshold'] !== false) $threshold = $_GET['threshold'];

    $script = "
    <script type='text/javascript' language='javascript'>
    // <![CDATA[
    // Script has to come after slider otherwise it doesn't work
    // It also has to be placed outside of a table or it doesn't work on IE6

    // initial slider value
    $('nbfThreshold').value = {$threshold};
    $('nbfPercentage').innerHTML = ({$threshold} * 100).toFixed(2) + '%';

    // horizontal slider control
    new Control.Slider('nbfHandle', 'nbfTrack', {
            sliderValue: {$threshold},
            onSlide: function(v) {
                $('nbfPercentage').innerHTML = (v * 100).toFixed(2) + '%';
                $('nbfThreshold').value = v;
            }
    });

    // ]]>
    </script>
    ";

    return $script;

}



?>