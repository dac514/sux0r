<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

function sux($action, $params = null) {

    switch($action)
    {

    case 'reset' :

        // --------------------------------------------------------------------
        // Reset password
        // --------------------------------------------------------------------

        include_once('userReset.php');
        $reset = new userReset();

        if ($reset->formValidate($_POST)) {
            $reset->formProcess($_POST);
            $reset->formSuccess();
        }
        else $reset->formBuild($_POST);

        break;

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


    case 'edit' :

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


    case 'avatar' :

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


    case 'openid' :

        // --------------------------------------------------------------------
        // Edit OpenIDs
        // --------------------------------------------------------------------

        $user = !empty($params[0]) ? $params[0]: null;

        // Edit avatar
        include_once('userOpenID.php');
        $oid = new userOpenID($user);

        if ($oid->formValidate($_POST)) {

            $oid->formProcess($_POST);
            $oid->formSuccess();

        }
        else $oid->formBuild($_POST);

        break;


    case 'profile' :

        // --------------------------------------------------------------------
        // Show user profile
        // --------------------------------------------------------------------

        include_once('userProfile.php');

        // Nickname
        if (empty($params[0])) {
            if (isset($_SESSION['nickname'])) {
                suxFunct::redirect(suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']));
            }
            else {
                suxFunct::redirect(suxFunct::makeUrl('/user/register'));
            }
        }

        $u = new userProfile($params[0]);

        if (!empty($params[1]) && $params[1] == 'rss') {
            // RSS
            $u->rss();
        }
        else {
            // Profile
            $u->displayProfile();
        }

        break;


    default:

        // --------------------------------------------------------------------
        // Redirect to homepage
        // --------------------------------------------------------------------

        suxFunct::redirect(suxFunct::makeUrl('/home'));

    }

}

?>