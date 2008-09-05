<?php

/**
* feedsRenderer
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
require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');
require_once(dirname(__FILE__) . '/../bayes/bayesRenderer.php');

class feedsRenderer extends suxRenderer {

    // Arrays
    public $fp = array(); // Array of first posts
    public $sidelist = array(); // Array of threads in sidebar

    // Objects
    private $bayesRenderer;
    private $rss;
    private $link;


    /**
    * Constructor
    *
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->bayesRenderer = new bayesRenderer('bayes');
        $this->rss = new suxRSS();
        $this->link = new suxLink();

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


    // ------------------------------------------------------------------------
    // Stuff like recent(), archives(), authors() is in the renderer because
    // there's no point in initializing if they aren't in the template
    // ------------------------------------------------------------------------
    
    
    /**
    * @return string html
    */    
    function isSubscribed($feed_id) {
        
        if (!$this->isLoggedIn())
            return  "<img src='{$this->url}/media/{$this->partition}/assets/sticky.gif' border='0' width='12' height='12' />";
              
        // Get config variables for template
        $tpl = new suxTemplate($this->module);
        $tpl->config_load('my.conf', $this->module);
        $image = $tpl->get_config_vars('imgUnsubscribed');
        
        // Don't query the database unnecessarily.
        static $img_cache = array();
        if (isset($img_cache[$feed_id])) {
            $image = $img_cache[$feed_id];
        }
        else {   
            // If subscribed, change image
            $query = 'SELECT COUNT(*) FROM link_rss_users WHERE rss_feeds_id = ? AND users_id = ? ';
            $db = suxDB::get();
            $st = $db->prepare($query);
            $st->execute(array($feed_id, $_SESSION['users_id']));
            if ($st->fetchColumn() > 0) $image = $tpl->get_config_vars('imgSubscribed');
            $img_cache[$feed_id] = $image;
        }
              
        $html = "<img src='{$this->url}/media/{$this->partition}/assets/{$image}' border='0' width='12' height='12'
        onclick=\"toggleSubscription('{$feed_id}');\" 
        style='cursor: pointer;'
        class='subscription{$feed_id}'
        />";
        
        return $html;
        
    }
    
    
    /**
    * @return string html
    */
    function feedLink($id) {
        
        $tmp = $this->rss->getFeed($id);
        if(!$tmp) return null;
        
        $url = suxFunct::makeUrl("/feeds/{$id}");
        $html = "<a href='{$url}'>{$tmp['title']}</a>";
        return $html;
        
    }
    
    


    /**
    *
    * @return array
    */
    function feeds($subscribed = false) {
        
        // Caches
        static $feeds = null;      
        static $subscriptions = null;
        
        if (!is_array($feeds)) $feeds = $this->rss->getFeeds();
        if (!is_array($subscriptions)) {
            $subscriptions = array();
            if (isset($_SESSION['users_id']))
                $subscriptions = $this->link->getLinks('link_rss_users', 'users', $_SESSION['users_id']);          
        }
                      
        $tmp = array();
        foreach($feeds as $feed) {
            if ($subscribed && in_array($feed['id'], $subscriptions)) {
                $tmp[] = $feed;
            }
            elseif (!$subscribed && !in_array($feed['id'], $subscriptions)) {
                $tmp[] =$feed;
            }
        }
        
        return $tmp;
        
    }



}


?>