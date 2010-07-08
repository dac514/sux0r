<?php

/**
* feeds
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once('itemsRenderer.php');
require_once(dirname(__FILE__) . '/../abstract.bayesComponent.php');
require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');


class items extends bayesComponent {

    // Module name
    protected $module = 'feeds';

    // Object: suxRSS()
    protected $rss;

    // Object: bayesUser()
    protected $nb;

    // Var: used by filter() method
    public $users_id;

		public $doCategorisation=false;

    /**
    * Constructor
    *
    */
    function __construct() {

        // Declare objects
        // $this->nb = new bayesUser();  (2.0.6)
				$this->nb = new suxUserNaiveBayesian();
        $this->rss = new suxRSS();
        $this->r = new itemsRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->r->bool['analytics'] = true; // Turn on analytics

    }


    function user($nickname) {

        // Get users_id based on nickname
        $user = $this->user->getByNickname($nickname);
        if (!$user) suxFunct::redirect(suxFunct::makeUrl('/feeds'));
        $this->users_id = $user['users_id']; // Needs to be in externally accessible variable for filter()
        unset($user);

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl("/feeds/user/$nickname"); // Forum Url
        $cache_id = false;

        $this->r->title .= " | {$this->r->gtext['feeds']} | $nickname";

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            // User has subscriptions, we need special JOIN queries
            $max = $this->countUserItems($this->users_id);
            $eval = '$this->getUserItems($this->users_id, $this->pager->limit, $start)';
            $this->r->arr['feeds']  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl("/feeds/user/$nickname", $params);
                $this->r->text['pager'] = $this->pager->continueURL($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = "$nn|user|$nickname|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                // User has subscriptions, we need special JOIN queries
                $this->pager->setPages($this->countUserItems($this->users_id));
                $this->r->arr['feeds'] = $this->getUserItems($this->users_id, $this->pager->limit, $this->pager->start);

                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/feeds/user/$nickname"));
                if (!count($this->r->arr['feeds'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        $this->tpl->assign('users_id', $this->users_id);
        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');


    }



    /**
    * Function user added by Santy to render APi results
    *
    * @param int $nickname
		* return array of feed items
    */
    function userAPI($nickname) {

		    $params = $URLparams = array();
		
        // Get users_id based on nickname
        $user = $this->user->getByNickname($nickname);
        if (!$user) {
				   //suxFunct::redirect(suxFunct::makeUrl('/feeds'));
					 header('HTTP/1.1 400 Bad Request'); // set the status
					 header("Status: 404 Bad Reques");
					 header('Content-type: application/xml'); // set the content type
					 echo "<?xml version=\"1.0\"?>\n<response code=\"400\" message=\"Bad Request\">Provided userNickname $nickname doesn't exist.</response>";
					 exit;
        }
				$this->users_id = $user['users_id']; // Needs to be in externally accessible variable for filter()
        unset($user);

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl("/feeds/user/$nickname"); // Forum Url
        $cache_id = false;

        $this->r->title .= " | {$this->r->gtext['feeds']} | $nickname";
				

				$maxHits = 0;
				$keywords = $threshold = $feed_id = $search = $vec_id = $cat_id = $start = '';
				if(array_key_exists('vec_id',$_GET)) $vec_id=$_GET['vec_id'];
				if(array_key_exists('amp;vec_id',$_GET)) $vec_id=$_GET['amp;vec_id'];
				if(array_key_exists('cat_id',$_GET)) $cat_id=$_GET['cat_id'];
				if(array_key_exists('amp;cat_id',$_GET)) $cat_id=$_GET['amp;cat_id'];
				if(array_key_exists('threshold',$_GET)) $threshold=$_GET['threshold'];
				if(array_key_exists('amp;threshold',$_GET)) $threshold=$_GET['amp;threshold'];
				if(array_key_exists('feed_id',$_GET)) $feed_id=$_GET['feed_id'];
				if(array_key_exists('amp;feed_id',$_GET)) $feed_id=$_GET['amp;feed_id'];
				if(array_key_exists('keywords',$_GET)) $search=$_GET['keywords'];
				if(array_key_exists('amp;keywords',$_GET)) $search=$_GET['amp;keywords'];
				if(array_key_exists('maxHits',$_GET)) $maxHits=$_GET['maxHits'];
				if(array_key_exists('amp;maxHits',$_GET)) $maxHits=$_GET['amp;maxHits'];
        
				if($start=='') $start=0;
				$this->doCategorisation = false;
				if($vec_id >0 && $cat_id >0) {
				   $this->doCategorisation = true;
				   if($threshold<0 || $threshold>1) $threshold = false; 
				}

				if($search=='') $search = false;
				if($feed_id>0) $maxHits = $this->countUserItems($this->users_id);
				if($this->doCategorisation || $search) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            // User has subscriptions, we need special JOIN queries
            $max = $this->countUserItems($this->users_id);
						if($maxHits=='all') $maxHits = $max;

						$eval = '$this->getUserItems($this->users_id, $max, $start)';  //today
						
						$this->r->arr['feeds']  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search,$maxHits); // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl("/feeds/user/$nickname", $params);
                $this->r->text['pager'] = $this->pager->continueURL($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = "$nn|user|$nickname|{$this->pager->start}";
            $this->tpl->caching = 1;

            $max = $this->countUserItems($this->users_id);
						if($maxHits=='all') $maxHits = $max;

						if($maxHits>0) $this->r->arr['feeds'] = $this->getUserItems($this->users_id, $maxHits, $this->pager->start);
            else $this->r->arr['feeds'] = $this->getUserItems($this->users_id, $this->pager->limit, $this->pager->start);
						

        }


				$this->r->arr['params'] = $params;

				return $this->r->arr['feeds'];
    }

		
		
		

    /**
    * Listing
    *
    * @param int $feeds_id a feed id
    */
    function listing($feeds_id = null) {

        // Check if the user has any subscriptions
        $subscriptions = array();
        if (isset($_SESSION['users_id'])) {
            $subscriptions = $this->link->getLinks('link__rss_feeds__users', 'users', $_SESSION['users_id']);
            $this->tpl->assign('users_id', $_SESSION['users_id']);
        }

        // Assign stuff
        $this->r->text['form_url'] = suxFunct::makeUrl("/feeds/$feeds_id"); // Forum Url
        $cache_id = false;

        // Title
        if ($feeds_id) {
            $this->r->title .= " | {$this->r->gtext['feed']}";
            $tmp = $this->rss->getFeedByID($feeds_id);
            if ($tmp) $this->r->title .= " | {$tmp['title']}";
        }
        else {
            $this->r->title .= " | {$this->r->gtext['feeds']}";
        }

        if (list($vec_id, $cat_id, $threshold, $start, $search) = $this->nb->isValidFilter()) {

            // ---------------------------------------------------------------
            // Filtered results
            // ---------------------------------------------------------------

            if ($feeds_id || !count($subscriptions)) {
                // Regular queries
                $max = $this->rss->countItems($feeds_id);
                $eval = '$this->rss->getItems($this->pager->limit, $start, ' . ($feeds_id ? $feeds_id : 'null') . ')';
            }
            else {
                // User has subscriptions, we need special JOIN queries
                $max = $this->countUserItems($_SESSION['users_id']);
                $eval = '$this->getUserItems($_SESSION[\'users_id\'], $this->pager->limit, $start)';
            }

            $this->r->arr['feeds']  = $this->filter($max, $vec_id, $cat_id, $threshold, $start, $eval, $search);  // Important: $start is a reference

            if ($start < $max) {
                if ($threshold !== false) $params = array('threshold' => $threshold, 'filter' => $cat_id);
                else $params = array('filter' => $cat_id);
                $params['search'] = $search;
                $url = suxFunct::makeUrl("/feeds/$feeds_id", $params);
                $this->r->text['pager'] = $this->pager->continueURL($start, $url);
            }


        }
        else {

            // ---------------------------------------------------------------
            // Paged results, cached
            // ---------------------------------------------------------------

            // Get nickname
            if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
            else $nn = 'nobody';

            $this->pager->setStart(); // Start pager

            // "Cache Groups" using a vertical bar |
            $cache_id = "$nn|listing|$feeds_id|{$this->pager->start}";
            $this->tpl->caching = 1;

            if (!$this->tpl->is_cached('scroll.tpl', $cache_id)) {

                if ($feeds_id || !count($subscriptions)) {
                    // Regular queries
                    $this->pager->setPages($this->rss->countItems($feeds_id));
                    $this->r->arr['feeds'] = $this->rss->getItems($this->pager->limit, $this->pager->start, $feeds_id);
                }
                else {
                    // User has subscriptions, we need special JOIN queries
                    $this->pager->setPages($this->countUserItems($_SESSION['users_id']));
                    $this->r->arr['feeds'] = $this->getUserItems($_SESSION['users_id'], $this->pager->limit, $this->pager->start);
                }

                $this->r->text['pager'] = $this->pager->pageList(suxFunct::makeUrl("/feeds/$feeds_id"));
                if (!count($this->r->arr['feeds'])) $this->tpl->caching = 0; // Nothing to cache, avoid writing to disk

            }

        }

        if ($cache_id) $this->tpl->display('scroll.tpl', $cache_id);
        else $this->tpl->display('scroll.tpl');

    }


    // -----------------------------------------------------------------------
    // Protected functions for $this->user() & this->listing()
    // -----------------------------------------------------------------------

    protected function countUserItems($users_id) {

        $db = suxDB::get();

        // Count
        $query = "
        SELECT COUNT(*) FROM rss_items
        INNER JOIN rss_feeds on rss_feeds.id = rss_items.rss_feeds_id
        INNER JOIN link__rss_feeds__users ON link__rss_feeds__users.rss_feeds_id = rss_feeds.id
        WHERE link__rss_feeds__users.users_id = ?
        ";
        $st = $db->prepare($query);
        $st->execute(array($users_id));
        return $st->fetchColumn();

    }


    protected function getUserItems($users_id, $limit, $start) {

        $db = suxDB::get();

        // Get Items
				// rss_items.id, rss_items.rss_feeds_id, rss_items.url, rss_items.title, rss_items.body_html as bodyhtml, rss_items.body_plaintext as body_html, rss_items.published_on
        // $query = " SELECT rss_items.* FROM rss_items
        $query = " SELECT rss_items.id, rss_items.rss_feeds_id, rss_items.url, rss_items.title, rss_items.body_plaintext, rss_items.body_plaintext as body_html, rss_items.published_on FROM rss_items
				INNER JOIN rss_feeds on rss_feeds.id = rss_items.rss_feeds_id
        INNER JOIN link__rss_feeds__users ON link__rss_feeds__users.rss_feeds_id = rss_feeds.id
        WHERE link__rss_feeds__users.users_id = ?
        ORDER BY rss_items.published_on DESC, rss_items.id DESC
        LIMIT {$limit} OFFSET {$start}
        ";

				$st = $db->prepare($query);
        $st->execute(array($users_id));

        return $st->fetchAll(PDO::FETCH_ASSOC);

    }
		

    public function getUserCategories($users_id, $cat_id=0) {

        $db = suxDB::get();
				// Get all the vectors & categories for an specific user:
				// SELECT bayes_auth.bayes_vectors_id, vector, bayes_categories.id, category FROM bayes_auth, bayes_vectors, bayes_categories 
				// WHERE bayes_auth.users_id=5 AND bayes_vectors.id=bayes_auth.bayes_vectors_id
				// AND bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id
				// AND bayes_categories.id=12 (if the category id is known)

				$userid=0;
				if(isset($users_id['users_id'])) $userid=$users_id['users_id'];	
        else return array();				
				$bayes_vectors_id = $id = $vector = $category = '';
				$q = "SELECT bayes_auth.bayes_vectors_id, vector, bayes_categories.id, category
				      FROM bayes_auth, bayes_vectors, bayes_categories WHERE bayes_auth.users_id = $userid
							AND bayes_vectors.id = bayes_auth.bayes_vectors_id
							AND bayes_categories.bayes_vectors_id = bayes_auth.bayes_vectors_id ";
				if($cat_id>0) $q .= "AND bayes_categories.id=$cat_id";
        $st = $db->query($q);
				$array = array();
				$i=0;
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
           extract($row);
					 $array[$i]['vec_id'] = $bayes_vectors_id;
					 $array[$i]['vec_name'] = $vector;
					 $array[$i]['cat_id'] = $id;
					 $array[$i]['cat_name'] = $category;
					 $i++;
				}

				return $array;
    }
		
		
    public function getURLparameters() {		
				$arrayParam = array();
				$array =array('vec_id' => 'vec_id', 'cat_id' => 'cat_id', 'threshold' => 'threshold', 'start' => 'start','search'=>'search','maxHits'=>'maxHits');
				$vec_id = $cat_id = $threshold= $start = $search = $maxHits = '';

				$arr = explode('/',$_GET['c']);
				

				foreach($array as $val) {
					 $i = array_search($val,$arr);

					 if(isset($arr[$i+1]) && $arr[$i+1]!='items') $arrayParam[$val] = $arr[$i+1];
				}

				return $arrayParam;
    }
				
}
?>