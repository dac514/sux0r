<?php

/**
* suxAuthenticate
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

class suxAuthenticate extends suxUser {

    /**
    * Constructor
    */
    function __construct($key = null) {
        // Call parent
        parent::__construct($key);
    }



    /**
    * Login
    */
    function login() {

        if ($this->loginCheck() || !$this->loginCheck() && $this->authenticate()) {

            // Redirect to user page
            $url = suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']);
            suxFunct::redirect($url);

        }
        else {

            // Too many password failures?
            if (isset($_SESSION['failures']) && $_SESSION['failures'] > $this->max_failures) {
                die('Too many password failures');
            }

            // Cancceled
            die('Cancelled');

        }

    }


    /**
    * Logout
    */
    function logout() {

        // Don't kill session (with password failures, perhaps?) if the
        // user isn't actually logged in.
        if ($this->loginCheck()) suxFunct::killSession();

        // Redirect to homepage
        suxFunct::redirect(suxFunct::makeUrl('/'));

    }



}


?>