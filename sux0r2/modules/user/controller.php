<?php

/**
* controller for user module
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

function sux($action, $params = null) {

    switch($action)
    {

    case 'login' :

        // --------------------------------------------------------------------
        // Login
        // --------------------------------------------------------------------

        if (!empty($params[0]) && $params[0] == 'openid') {

            // Openid registration
            include_once('suxLoginOpenID.php');
            $auth = new suxLoginOpenID();

            if ($auth->formValidate($_POST)) $auth->formHandoff($_POST);
            else $auth->formBuild($_POST);

        }
        else {

            // Regular login
            include_once('suxAuthenticate.php');
            $auth = new suxAuthenticate();
            $auth->login();
        }
        break;


    case 'logout' :

        // --------------------------------------------------------------------
        // Logout
        // --------------------------------------------------------------------

        include_once('suxAuthenticate.php');
        $auth = new suxAuthenticate();
        $auth->logout();
        break;


    case 'register' :

        // --------------------------------------------------------------------
        // Register
        // --------------------------------------------------------------------

        if (!empty($params[0]) && $params[0] == 'openid') {

            // Openid registration
            include_once('suxRegisterOpenID.php');
            $reg = new suxRegisterOpenID();

            if ($reg->formValidate($_POST)) $reg->formHandoff($_POST);
            else $reg->formBuild($_POST);

        }
        else {

            // Regular registration
            include_once('suxEdit.php');
            $reg = new suxEdit();

            if ($reg->formValidate($_POST)) {

                $reg->formProcess($_POST);
                $reg->formSuccess();

            }
            else $reg->formBuild($_POST, $_GET);
        }

        break;


    case 'edit' : // User profile

        // --------------------------------------------------------------------
        // Edit Registration
        // --------------------------------------------------------------------

            $user = !empty($params[0]) ? $params[0]: null;

            // Edit profile registration
            include_once('suxEdit.php');
            $reg = new suxEdit('edit', $user);

            if ($reg->formValidate($_POST)) {

                $reg->formProcess($_POST);
                $reg->formSuccess();

            }
            else $reg->formBuild($_POST, $_GET);

            break;



    case 'profile' : // User profile

        // --------------------------------------------------------------------
        // Show user profile
        // --------------------------------------------------------------------

        include_once('suxProfile.php');
        if (!empty($params[0])) {
            $u = new suxProfile($params[0]);
            if ($u->profile) $u->displayProfile();
            else $u->notFound();
            break;
        }

    default:

        // --------------------------------------------------------------------
        // Default
        // --------------------------------------------------------------------

        echo 'user module';
        break;

    }

}

?>