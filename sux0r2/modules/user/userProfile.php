<?php

/**
* userProfile
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxSocialNetwork.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('userRenderer.php');

class userProfile {

    // Variables
    public $gtext = array();
    public $profile; // User profile array
    private $module = 'user';

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $minifeed_limit = 10;


    /**
    * Constructor
    *
    * @param string $nickname nickname
    */
    function __construct($nickname) {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new userRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->r->bool['analytics'] = true; // Turn on analytics

        // Profile
        $this->profile = $this->user->getUserByNickname($nickname, true);
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

        if(!$this->tpl->is_cached('profile.tpl', $cache_id)) {


            if (!isset($this->profile['dob']) || $this->profile['dob'] == '0000-00-00') unset($this->profile['dob']); // NULL date

            $this->r->arr['profile'] =& $this->profile; // Assign
            $this->r->title .= " | {$this->profile['nickname']}";
            $this->r->arr['minifeed'] = $this->user->getLog($this->minifeed_limit, 0, $this->profile['users_id']); // Minifeed array

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

        if (!$this->tpl->is_cached('rss.tpl', $cache_id)) {

            $fp = $this->user->getLog(($this->minifeed_limit * 5), 0, $this->profile['users_id']);
            if ($fp) {

                require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
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