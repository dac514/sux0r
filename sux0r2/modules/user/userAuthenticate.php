<?php

/**
* userAuthenticate
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

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class userAuthenticate {

    // Variables
    public $gtext = array(); // Language
    private $module = 'user'; // Module


    // Objects
    public $tpl;
    public $r;
    private $user;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new suxRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;

    }



    /**
    * Login
    */
    function login() {

        if ($this->user->loginCheck() || !$this->user->loginCheck() && $this->user->authenticate()) {

            $this->user->log("sux0r::userAuthenticate() login [IP: {$_SERVER['REMOTE_ADDR']}]", $_SESSION['users_id'], 1); // Log, private

            // Redirect to previous page
            if (isset($_SESSION['breadcrumbs'])) foreach($_SESSION['breadcrumbs'] as $val) {
                if (!preg_match('#^user/[login|logout|register|edit]#i', $val)) {
                    suxFunct::redirect(suxFunct::makeUrl($val));
                    break;
                }
            }

            // Nothing of value was found, redirect to user page
            suxFunct::redirect(suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']));

        }
        else {

            // Too many password failures?
            if ($this->user->maxPasswordFailures()) {
                $this->r->title .= " | {$this->r->text['pw_failure']}";
                $this->tpl->display('pw_failure.tpl');
                die();
            }

            // Note:
            // Threre's a conflift with the authenticate procedure and header('Location:')
            // The workaround is to echo some spaces and force javascript redirect

            echo str_repeat(' ', 40000);
            suxFunct::redirect(suxFunct::makeUrl('/home'));

        }

    }


    /**
    * Logout
    */
    function logout() {

        // Don't kill session (with password failures, perhaps?) if the
        // user isn't actually logged in.
        if ($this->user->loginCheck()) {
            $this->user->log('sux0r::userAuthenticate() logout', $_SESSION['users_id'], 1); // Log, private
            suxFunct::killSession();
        }

        // Ask browser to clear authentication
        header('HTTP/1.0 401 Unauthorized');

        $this->r->title .= " | {$this->r->text['logout']}";

        // Template
        $this->tpl->display('logout.tpl');

    }


}


?>