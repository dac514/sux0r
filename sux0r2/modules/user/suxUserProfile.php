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

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxSocialNetwork.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxUrl.php');

class suxUserProfile extends suxUser {

    public $tpl;
    private $profile;

    function __construct($nickname, $key = null) {

        // Call parent
        parent::__construct($key);

        // Template
        $this->tpl = new suxTemplate('openid', $GLOBALS['CONFIG']['PARTITION']);
        $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']);

        // Profile
        $this->profile = $this->getUserByNickname($nickname, true);
        unset($this->profile['password']); // We don't need this

    }


    function render() {

        if ($this->profile) $this->showProfile();
        else $this->notFound();

    }


    function notFound() {

        echo 'no profile found';

    }


    function showProfile() {

        new dBug($this->profile);

    }



}


?>