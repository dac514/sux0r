<?php

/**
* suxBayesRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../includes/suxRenderer.php');
require_once(dirname(__FILE__) . '/../extensions/suxUserNaiveBayesian.php');

class suxBayesRenderer extends suxRenderer {

    // Object: suxUserNaiveBayesian()
    protected $nb;

    // Object: suxLink();
    protected $link;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->nb = new suxUserNaiveBayesian();
        $this->link = new suxLink();

    }


    /**
    * @global string $CONFIG['URL']
    * @param bool $init include .js files?
    * @return string the javascript code
    */
    function genericBayesInterfaceInit($init = true) {

        if ($GLOBALS['CONFIG']['FEATURE']['bayes'] == false) return null; // Feature is turned off
        if (!isset($_SESSION['users_id'])) return null; // Skip anonymous users

        $js ="
        <script type='text/javascript'>
        // <![CDATA[

        function suxTrain(placeholder, link, module, id, cat_id) {

            var url = '{$GLOBALS['CONFIG']['URL']}/modules/bayes/ajax.train.php';
            var pars = { link: link, module: module, id: id, cat_id: cat_id };

            $.ajax({
                url: url,
                type: 'post',
                data: pars,
                beforeSend: function() {
                    $(placeholder).effect('highlight', {}, 1000);
                },
                success: function() {
                    $(placeholder).addClass('nbVecTrained');
                    $(placeholder).effect('pulsate');
                },
                error: function(transport)  {
                    if ($.trim(transport.responseText).length) {
                        alert(transport.responseText);
                    }
                }
                });
        }

        function suxNotTrainer(placeholder) {
            $(placeholder).effect('shake', { distance: 5 }, 100);
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

        if ($GLOBALS['CONFIG']['FEATURE']['bayes'] == false) return null; // Feature is turned off

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

        $link_table = $this->link->buildTableName($link, 'bayes_documents');
        $innerjoin = "
        INNER JOIN bayes_auth ON bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
        INNER JOIN bayes_documents ON bayes_categories.id = bayes_documents.bayes_categories_id
        INNER JOIN {$link_table} ON {$link_table}.bayes_documents_id = bayes_documents.id
        INNER JOIN {$link} ON {$link_table}.{$link}_id = {$link}.id
        ";

        $query = "
        SELECT bayes_categories.id FROM bayes_categories
        {$innerjoin}
        WHERE {$link}.id = ? AND bayes_auth.users_id = ?
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
                    $html .= "%_{$uniqid}_%"; // Action to be replaced
                    $html .= "=\"suxTrain('#nb{$uniqid}', '{$link}', '{$module}', {$id}, this.options[selectedIndex].value);\" ";
                    $html .= '>';

                }
                else {
                    // this is $v_user[], sit pretty, do nothing
                    $html .= '<select name="null" class="nbCatDropdown" ';
                    $html .= "onchange=\"suxNotTrainer('#nb{$uniqid}');\" ";
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


                /* Replace strings */

                if ($is_categorized) {
                    $replace = "<span class='nbVecTrained'>{$val['vector']} : </span>";
                    $replace2 = 'onchange';
                }
                else {
                    $replace = $val['vector'] . ' : ';
                    $replace2 = 'onmouseup';
                }
                $html = str_replace("@_{$uniqid}_@", $replace, $html);
                $html = str_replace("%_{$uniqid}_%", $replace2, $html);


                /* Get bayesian scores */

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
    * Get vectors, statically cached array
    *
    * @return array
    */
    protected function getVectorsByOwnerArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getVectorsByOwner($_SESSION['users_id']);

    }


    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    protected function getVectorsByUserArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getVectorsByUser($_SESSION['users_id']);

    }


    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    protected function getVectorsByTrainerArray() {

        static $vectors = array();
        if (count($vectors)) return $vectors; // Cache
        else return $this->nb->getVectorsByTrainer($_SESSION['users_id']);

    }




    /**
    * Get vectors, statically cached array
    *
    * @return array
    */
    protected function getSharedVectorsArray() {

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

    if ($GLOBALS['CONFIG']['FEATURE']['bayes'] == false) return null; // Feature is turned off
    if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

    $r = new suxBayesRenderer('bayes'); // Renderer
    if (!$r->getUserCategories()) return null; // No categories, skip

    $tpl = new suxTemplate('bayes'); // Template
    $r->gtext = suxFunct::gtext('bayes'); // Language

    if (isset($_GET['filter'])) $tpl->assign('filter', $_GET['filter']);
    if (isset($_GET['threshold']) && $_GET['threshold'] !== false) $tpl->assign('threshold', $_GET['threshold']);
    if (isset($_GET['search'])) $tpl->assign('search', strip_tags($_GET['search']));
    if (isset($params['form_url'])) $r->text['form_url'] = $params['form_url'];
    if (isset($params['hidden']) && is_array($params['hidden'])) $r->arr['hidden'] = $params['hidden'];

    if (!$GLOBALS['CONFIG']['CLEAN_URL']) $r->text['c'] = @$_GET['c']; // We need this if CLEAN_URL = false

    $tpl->assignByRef('r', $r);
    return $tpl->fetch('filters.tpl');

}


/**
* Render bayesFilterScript
*
* @return string javascript
*/
function insert_bayesFilterScript() {

    if ($GLOBALS['CONFIG']['FEATURE']['bayes'] == false) return null; // Feature is turned off
    if (!isset($_SESSION['users_id'])) return null; // Anonymous user, skip

    $r = new suxBayesRenderer('bayes'); // Renderer
    if (!$r->getUserCategories()) return null; // No categories, skip

    $threshold = 0;
    if (isset($_GET['threshold']) && $_GET['threshold'] !== false) $threshold = $_GET['threshold'];

    $script = "
    <script type='text/javascript' language='javascript'>
    // <![CDATA[
    // Script has to come after slider otherwise it doesn't work
    // It also has to be placed outside of a table or it doesn't work on IE6
    // TODO: Do we care?

    // initial slider value
    $('#nbfThreshold').val({$threshold});
    $('#nbfPercentage').html(({$threshold} * 100).toFixed() + '%');

    // horizontal slider control

    $(function() {
        $('#nbfSlider').slider({
            value: {$threshold} * 100,
            range: 'min',
            slide: function( event, ui ) {
                $('#nbfPercentage').html(ui.value  + '%');
                $('#nbfThreshold').val(ui.value * .01);
            }
        });
    });

    // ]]>
    </script>
    ";

    return $script;

}


?>