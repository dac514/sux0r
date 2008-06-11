<?php

/**
* suxProfile
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

class suxProfile extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    public $profile; // User profile array
    private $module = 'user'; // Module

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @param string $nickname nickname
    */
    function __construct($nickname) {

        parent::__construct(); // Call parent
        $this->tpl = new suxTemplate($this->module, $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->r = new renderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;

        // Profile
        $this->profile = $this->getUserByNickname($nickname);
        unset($this->profile['password']); // We don't need this

    }


    /**
    * Display user profile
    */
    function displayProfile() {

        $this->tpl->caching = 1; // Enable cache
        $cache_id = $this->profile['nickname'];

        if(!$this->tpl->is_cached('profile.tpl', $cache_id)) {

            // Full Profile
            $fullprofile = $this->getUser($this->profile['users_id'], true);
            unset($fullprofile['password']); // We don't need this
            if (!isset($fullprofile['dob']) || $fullprofile['dob'] == '0000-00-00') unset($fullprofile['dob']); // NULL date
            $this->r->profile =& $fullprofile; // Assign

            // Title
            $this->r->title .= " | {$fullprofile['nickname']}";

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