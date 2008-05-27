<?php

/**
* suxUserProfile
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

require_once(dirname(__FILE__) . '/../../includes/symbionts/calendar.php');
require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxSocialNetwork.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once('renderer.php');

class suxUserProfile extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    public $profile; // User profile array
    private $module = 'user'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @param string $nickname nickname
    * @param string $key PDO dsn key
    */
    function __construct($nickname, $key = null) {

        parent::__construct($key); // Call parent
        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language
        $this->r = new renderer($this->module); // Renderer
        $this->r->text =& $this->gtext; // Language

        // Profile
        $this->profile = $this->getUserByNickname($nickname);
        unset($this->profile['password']); // We don't need this

    }


    /**
    * Display user profile
    */
    function displayProfile() {

        $this->tpl->caching = 1; // Enable cache
        $cache_id = "{$this->profile['nickname']}_";

        if(!$this->tpl->is_cached('profile.tpl', $cache_id)) {

            // Full Profile
            $fullprofile = $this->getUser($this->profile['users_id'], true);
            unset($fullprofile['password']); // We don't need this
            $this->r->profile =& $fullprofile;

            // Title
            $this->r->title .= " | {$fullprofile['nickname']}";

            // OpenID Server meta tags
            $this->r->header .= $this->r->getOpenIDMeta();

            // TODO: Calendar
            $this->r->text['calendar'] = generate_calendar(2008, 5);

            $this->tpl->assign_by_ref('r', $this->r);

        }

        $this->tpl->display('profile.tpl', $cache_id);

    }


    /**
    * Profile not found
    */
    function notFound() {

        echo 'no profile found';
        exit;

    }



}


?>