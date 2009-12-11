<?php

/**
* Abstract Bayesian Component class
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/abstract.component.php');
require_once(dirname(__FILE__) . '/../extensions/bayesUser.php');


abstract class bayesComponent extends component {

    // Object: bayesUser()
    protected $nb;

    /**
    * Constructor
    */
    function __construct() {

        // Pre-condition sanity check
        if (!($this->nb instanceof bayesUser))
            throw new Exception('$this->nb is not an instance of bayesUser()');

        parent::__construct(); // Let the parent do the rest

    }


    /**
    * Filter
    *
    * @param int $max
    * @param int $vec_id
    * @param int $cat_id
    * @param float $threshold
    * @param int &$start Important: is a reference
    * @param string $eval
    * @param string $search
    */
    protected function filter($max, $vec_id, $cat_id, $threshold, &$start, $eval, $search, $maxHits = 0) {

        // -------------------------------------------------------------------
        // Get items based on score, variable paging
        // -------------------------------------------------------------------

        $results = array();

        // Force timeout if this operation takes too long
        $timer = microtime(true);
        $timeout_max = ini_get('max_execution_time') * 0.333333;
        if ($timeout_max > 30) $timeout_max = 30;

        $search = trim(strip_tags($search));
        if ($search) {
            $rawtokens = mb_split("\W", $search);
            foreach ($rawtokens as $k => $v) {
                if (!trim($v)) unset($rawtokens[$k]);
            }
            $rawtoken_count = count($rawtokens);
        }

        // Start filtering
        $i = 0;
				if($maxHits<0 || !isset($maxHits)) $maxHits = $max;

				if ($search) $limit = $max;
				else $limit = $maxHits; //$this->pager->limit;

        $ok = array();

            $tmp = array();
            eval('$tmp = ' . $eval . ';'); // results is transformed here, by $eval
            foreach ($tmp as $val) {
                // array_merge renumbers, avoid this by appending in a foreach loop
                $results[] = $val;
								
            }

            foreach ($results as $key => $val) {
                if (isset($ok[$key])) continue; // Don't recalculate
								$results[$key]['score'] = '';
								if($this->doCategorisation) {
								
									 $score = $this->nb->passesThreshold($threshold, $vec_id, $cat_id, "{$val['title']} {$val['body_plaintext']}");
									 if (!$score) {
								       unset($results[$key]);
                       continue; // No good, skip it
                   } else $results[$key]['score'] = $score;
                }
								//$search=false;
                if ($search) {
                    $found = 0;
                    foreach ($rawtokens as $token) {
                        if (mb_stripos("{$val['title']} {$val['body_plaintext']}", $token) !== false)
                            ++$found;
                    }
                    if ($found != $rawtoken_count) {
                        unset($results[$key]);
                        continue; // No good, skip it
                    }
                }
                $ok[$key] = true; // It's good, remember it
            }

            $i = count($results);
            $start = $start + $this->pager->limit;

						if(sizeof($results)>$maxHits) {
						if ($i < $limit && $start < ($maxHits) && ($timer + $timeout_max) > microtime(true)) {
                // Not enough first posts, keep looping
                $this->pager->limit = 1;
            }

						}

            $this->pager->limit = $limit; // Restore limit

				
						$array = array();
						$ii=0;
						if(sizeof($results>$maxHits)) {
						   foreach($results as $resu) {
							    $array[$ii] = $resu;
									$ii++;
									if($ii==$maxHits) break;
						   }
							 $results = array();
					  	 $results = $array;				
				    }

        		return $results;

    }


}

?>
