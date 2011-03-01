<?php

/**
* feedsRenderer
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class feedsRenderer extends suxBayesRenderer {

    // Object: suxRss()
    private $rss;

    /**
    * Constructor
    *
    * @global string $CONFIG['FEATURE']
    * @param string $module
    */
    function __construct($module) {

        parent::__construct($module); // Call parent
        $this->rss = new suxRSS();
        $this->bool['bayes'] = $GLOBALS['CONFIG']['FEATURE']['bayes_feeds'];

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
            return  "<img src='{$this->url}/media/{$this->partition}/assets/sticky.gif' border='0' width='12' height='12' alt='' />";

        // Get config variables for template
        $tpl = new suxTemplate($this->module);
        $tpl->configLoad('my.conf', $this->module);
        $image = $tpl->getConfigVars('imgUnsubscribed');

        // Don't query the database unnecessarily.
        static $img_cache = array();
        if (isset($img_cache[$feed_id])) {
            $image = $img_cache[$feed_id];
        }
        else {
            // If subscribed, change image
            $query = 'SELECT COUNT(*) FROM link__rss_feeds__users WHERE rss_feeds_id = ? AND users_id = ? ';
            $db = suxDB::get();
            $st = $db->prepare($query);
            $st->execute(array($feed_id, $_SESSION['users_id']));
            if ($st->fetchColumn() > 0) $image = $tpl->getConfigVars('imgSubscribed');
            $img_cache[$feed_id] = $image;
        }

        $html = "<img src='{$this->url}/media/{$this->partition}/assets/{$image}' border='0' width='12' height='12' alt=''
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

        $tmp = $this->rss->getFeedByID($id);
        if(!$tmp) return null;

        $url = suxFunct::makeUrl("/feeds/{$id}");
        $html = "<a href='{$url}'>{$tmp['title']}</a>";
        return $html;

    }




    /**
    *
    * @return array
    */
    function feeds($subscribed = false, $users_id = null) {

        // Caches
        static $feeds = null;
        static $subscriptions = null;

        if (!is_array($feeds)) $feeds = $this->rss->getFeeds();
        if (!is_array($subscriptions)) {
            $subscriptions = array();
            if (isset($users_id))
                $subscriptions = $this->link->getLinks('link__rss_feeds__users', 'users', $users_id);
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


    /**
    * TinyMCE Initialization for bookmarks
    *
    * @see http://tinymce.moxiecode.com/
    * @return string the javascript code
    */
    function tinyMceEditor() {

        $init = '
        mode : "textareas",
        theme : "advanced",
        editor_selector : "mceEditor",
        plugins : "safari,paste,inlinepopups,autosave",
        width: "100%",
        height: 100,
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_buttons1 : "undo,redo,pastetext,pasteword,selectall,|,bold,italic,underline,strikethrough,|,link,unlink,|,cleanup,code",
        theme_advanced_buttons2 : "",
        theme_advanced_buttons3 : "",
        theme_advanced_statusbar_location : "bottom",
        entity_encoding : "raw",
        relative_urls : false,
        inline_styles : false,
        ';
        return $this->tinyMce($init);

    }


}


?>