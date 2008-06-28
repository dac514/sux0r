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
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->nb = new bayesUser();
        $this->link = new suxLink();

    }

    // ---

    /**
    * @param int $id messages id
    * @param string $link link table
    * @param string $document document to train
    * @return string html
    */
    function genericBayesInterface($id, $link, $document) {

        if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

        /* Get a list of all the vectors/categories the user has access to */

        // Cache
        static $vectors = null;
        if (!is_array($vectors)) {
            $vectors = array();
            foreach ($this->nb->getVectorsByUser($_SESSION['users_id']) as $key => $val) {
                $vectors[$key] = $val['vector'];
            }
        }
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
        $links = $this->link->getLinks($this->link->getLinkTableName($link, 'bayes'), $link, $id);
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


    // ---


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

        foreach ($this->getUserOwnedVectorsArray() as $key => $val) {
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

        foreach ($this->getUserSharedVectorsArray() as $key => $val) {
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

        foreach ($this->getUserOwnedVectorsArray() as $key => $val) {

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
    * Get documents
    *
    * @return array
    */
    function getUserOwnedDocuments() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        foreach ($this->getUserOwnedVectorsArray() as $key => $val) {
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
        foreach ($this->getUserSharedVectorsArray() as $key => $val) {
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

        // Yes, we could have left joined the users table
        //
        // But because we can split our data among multiple databases we
        // can't guarantee that the users tables and the bayes tables are
        // in the same place, hence this awkwardness

        require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
        $user = new suxUser();

        // Owned, and the users shared with
        $vectors = $this->getUserOwnedVectorsArray();
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
        $vectors = $this->getUserSharedVectorsArray();
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
    private function getUserOwnedVectorsArray() {

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
    private function getUserSharedVectorsArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getSharedVectors($_SESSION['users_id']);

    }



}


?>