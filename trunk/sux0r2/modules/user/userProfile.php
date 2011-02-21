<?php

/**
* userProfile
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class userProfile extends component {

    // Module name
    protected $module = 'user';

    // Var: user profile array
    public $profile;

    // Var: minifeed limit
    private $minifeed_limit = 10;


    /**
    * Constructor
    *
    * @param string $nickname nickname
    */
    function __construct($nickname) {


        // Declare objects
        $this->r = new userRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

        // Declare properties
        $this->r->bool['analytics'] = true; // Turn on analytics
        $this->profile = $this->user->getByNickname($nickname, true);

        unset($this->profile['password']); // We don't need this
        if (!$this->profile) suxFunct::redirect(suxFunct::getPreviousURL()); // Redirect for invalid profiles

    }


    /**
    * Display user profile
    */
    function displayProfile() {

        // Get nickname
        if (isset($_SESSION['nickname'])) $nn = $_SESSION['nickname'];
        else $nn = 'nobody';

        $cache_id = "$nn|{$this->profile['nickname']}";
        $this->tpl->caching = 1;

        if(!$this->tpl->isCached('profile.tpl', $cache_id)) {


            if (!isset($this->profile['dob']) || $this->profile['dob'] == '0000-00-00') unset($this->profile['dob']); // NULL date

            $this->r->arr['profile'] =& $this->profile; // Assign
            $this->r->title .= " | {$this->profile['nickname']}";
            $this->r->arr['minifeed'] = $this->log->get($this->minifeed_limit, 0, $this->profile['users_id']); // Minifeed array

        }

        $this->tpl->display('profile.tpl', $cache_id);

    }


    /**
    * Display RSS Feed
    */
    function rss() {

        // Cache
        $cache_id = $this->profile['nickname'] . '|rss';
        $this->tpl->caching = 1;

        if (!$this->tpl->isCached('rss.tpl', $cache_id)) {

            $fp = $this->log->get(($this->minifeed_limit * 5), 0, $this->profile['users_id']);
            if ($fp) {                
                $rss = new suxRSS();
                $title = "{$this->r->title} | {$this->profile['nickname']}";
                $url = suxFunct::makeUrl('/user/profile/' . $this->profile['nickname'], null, true);
                $rss->outputRSS($title, $url, null);

                foreach($fp as $item) {
                    $url2 = $url . '#' . strtotime($item['ts']);
                    $rss->addOutputItem($item['ts'], $url2, $item['body_html']);
                }

                $this->tpl->assign('xml', $rss->saveXML());
            }

        }

        // Template
        header('Content-type: text/xml; charset=utf-8');
        $this->tpl->display('rss.tpl', $cache_id);

    }





}


?>