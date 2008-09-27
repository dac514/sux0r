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

    case 'admin' :

        // --------------------------------------------------------------------
        // Admin
        // --------------------------------------------------------------------

        include_once('photosAdmin.php');
        $admin = new photosAdmin();

        if ($admin->formValidate($_POST)) {
            $admin->formProcess($_POST);
            $admin->formSuccess();
        }
        else {
            $admin->formBuild($_POST);
        }


        break;


    case 'view':

        // --------------------------------------------------------------------
        // View
        // --------------------------------------------------------------------

        if (empty($params[0]) || !filter_var($params[0], FILTER_VALIDATE_INT) || $params[0] < 1) {
            suxFunct::redirect(suxFunct::makeUrl('/photos'));
        }

        include_once('photos.php');
        $photos = new photos();
        $photos->view($params[0]);
        break;


    case 'upload':

        // --------------------------------------------------------------------
        // Upload
        // --------------------------------------------------------------------

        include_once('photoUpload.php');
        $edit = new photoUpload(@$params[0]);

        if ($edit->formValidate($_POST)) {
            $edit->formProcess($_POST);
            $edit->formSuccess();
        }
        else {
            $edit->formBuild($_POST);
        }

        break;



    case 'album':

        // --------------------------------------------------------------------
        // Edit
        // --------------------------------------------------------------------

        if ($params[0] == 'edit') {

            $id = !empty($params[1]) ? $params[1]: null;

            include_once('photoalbumsEdit.php');
            $edit = new photoalbumsEdit($id);

            if ($edit->formValidate($_POST)) {
                $edit->formProcess($_POST);
                $edit->formSuccess();
            }
            else {
                $edit->formBuild($_POST);
            }

            break;
        }

        // --------------------------------------------------------------------
        // Annotate
        // --------------------------------------------------------------------


        elseif ($params[0] == 'annotate') {

            if (empty($params[1]) || !filter_var($params[1], FILTER_VALIDATE_INT) || $params[1] < 1) {
                suxFunct::redirect(suxFunct::makeUrl('/photos'));
            }

            include_once('photosEdit.php');
            $edit = new photosEdit($params[1]);

            if ($edit->formValidate($_POST)) {
                $edit->formProcess($_POST);
                $edit->formSuccess();
            }
            else {
                $edit->formBuild($_POST);
            }


            break;
        }


        // --------------------------------------------------------------------
        // View
        // --------------------------------------------------------------------

        else {

            if (empty($params[0]) || !filter_var($params[0], FILTER_VALIDATE_INT) || $params[0] < 1) {
                suxFunct::redirect(suxFunct::makeUrl('/photos'));
            }

            include_once('photos.php');
            $photos = new photos();
            $photos->album($params[0]);
            break;

        }


    case 'user' :

        // --------------------------------------------------------------------
        // User
        // --------------------------------------------------------------------

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/photos'));
        }

        include_once('photos.php');
        $photos = new photos();
        $photos->listing($params[0]);

        break;

    case 'rss':

        // --------------------------------------------------------------------
        // RSS
        // --------------------------------------------------------------------

        include_once('photos.php');
        $photos = new photos();
        $photos->rss();
        break;


    default:

        include_once('photos.php');
        $photos = new photos();
        $photos->listing();
        break;

    }

}

?>