<?php

/**
* bayesRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class bayesRenderer extends suxBayesRenderer {


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent

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
            if (count($y) < 2) continue; // Skip
            $tmp[$x] = $y;
        }

        return $tmp;

    }


    /**
    * Get {html_options} formated friends array
    *
    * @return array
    */
    function getFriends() {

        // Cache
        static $tmp = null;
        if (is_array($tmp)) return $tmp;
        $tmp = array();

        $soc = new suxSocialNetwork();
        $user = new suxUser();

        $rel = $soc->getRelationships($_SESSION['users_id']);
        if (!$rel) return $tmp;

        foreach ($rel as $val) {
            $u = $user->getByID($val['friend_users_id']);
            if (!$u) continue; // Skip
            $tmp[$val['friend_users_id']] = $u['nickname'];
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

        $cat = 0;
        $html = "<ul id='bStats'>\n";
        foreach ($this->getSharedVectorsArray() as $key => $val) {
            $html .= "<li class='bStatsVec'>{$val['vector']}";
            if (!$this->nb->isVectorOwner($key, $_SESSION['users_id'])) $html .= ' <em>(' . $this->gtext['shared'] . ')</em>';
            $html .= ":\n<ul>\n";
            foreach ($this->nb->getCategoriesByVector($key) as $key2 => $val2) {
                $doc_count = $this->nb->getDocumentCountByCategory($key2);
                $html .= "<li class='bStatsCat'>{$val2['category']}:";
                $html .= "<ul>\n";
                $html .= "<li class='bStatsDoc'>{$this->gtext['documents']}: $doc_count</li><li class='bStatsTok'>{$this->gtext['tokens']}: {$val2['token_count']}</li>";
                $html .= "<li class='bStatsProb'>P: " . round($val2['probability'] * 100, 2) . " %</li>\n";
                $html .= "</ul></li>\n";
                ++$cat;
            }
            $html .= "</ul></li>\n";
        }
        $html .= "</ul>\n";

        if (!$cat) return null;
        else return $html;
    }


    /**
    * @return string html table
    */
    function getShareTable() {

        static $html = null;
        if ($html) return $html; // Cache

        $html .= "<table class='shared'><thead><tr>
        <th>{$this->gtext['vector']}</th>
        <th>{$this->gtext['user']}</th>
        <th>{$this->gtext['trainer']}</th>
        <th>{$this->gtext['owner']}</th>
        <th>{$this->gtext['unshare']}</th>
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

                $u = $user->getByID($val2['users_id']);

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


}

