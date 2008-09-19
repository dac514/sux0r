<?php

/**
* controller
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
            include_once('userLoginOpenID.php');
            $auth = new userLoginOpenID();

            if ($auth->formValidate($_POST)) $auth->formHandoff($_POST);
            else $auth->formBuild($_POST);

        }
        else {

            // Regular login
            include_once('userAuthenticate.php');
            $auth = new userAuthenticate();
            $auth->login();
        }
        break;


    case 'logout' :

        // --------------------------------------------------------------------
        // Logout
        // --------------------------------------------------------------------

        include_once('userAuthenticate.php');
        $auth = new userAuthenticate();
        $auth->logout();
        break;


    case 'register' :

        // --------------------------------------------------------------------
        // Register
        // --------------------------------------------------------------------

        if (!empty($params[0]) && $params[0] == 'openid') {

            // Openid registration
            include_once('userRegisterOpenID.php');
            $reg = new userRegisterOpenID();

            if ($reg->formValidate($_POST)) $reg->formHandoff($_POST);
            else $reg->formBuild($_POST);

        }
        else {

            // Regular registration
            include_once('userEdit.php');
            $reg = new userEdit();

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
        include_once('userEdit.php');
        $reg = new userEdit('edit', $user);

        if ($reg->formValidate($_POST)) {

            $reg->formProcess($_POST);
            $reg->formSuccess();

        }
        else $reg->formBuild($_POST, $_GET);

        break;


    case 'avatar' : // User avatar

        // --------------------------------------------------------------------
        // Edit Avatar
        // --------------------------------------------------------------------

        $user = !empty($params[0]) ? $params[0]: null;

        // Edit avatar
        include_once('userAvatar.php');
        $reg = new userAvatar($user);

        if ($reg->formValidate($_POST)) {

            $reg->formProcess($_POST);
            $reg->formSuccess();

        }
        else $reg->formBuild($_POST);

        break;


    case 'profile' : // User profile

        // --------------------------------------------------------------------
        // Show user profile
        // --------------------------------------------------------------------

        include_once('userProfile.php');

        if (empty($params[0])) {
            if (isset($_SESSION['nickname'])) {
                suxFunct::redirect(suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']));
            }
            else {
                suxFunct::redirect(suxFunct::makeUrl('/user/register'));
            }
        }

        $u = new userProfile($params[0]);
        if ($u->profile) $u->displayProfile();
        else $u->notFound();
        break;

    default:

        // --------------------------------------------------------------------
        // Redirect to homepage
        // --------------------------------------------------------------------

        suxFunct::redirect(suxFunct::makeUrl('/home'));

    }

}

?>