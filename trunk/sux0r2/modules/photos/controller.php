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

    case 'view':

        include_once('photos.php');
        $photos = new photos();
        $photos->view($params[0]);
        break;


    case 'upload':

        // --------------------------------------------------------------------
        // Upload
        // --------------------------------------------------------------------

        include_once('photoUpload.php');
        $edit = new photoUpload();

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
        // annotate
        // --------------------------------------------------------------------

        if ($params[0] == 'annotate') {

            $id = !empty($params[1]) ? $params[1]: null;

            include_once('photosEdit.php');
            $edit = new photosEdit($id);
            $edit->annotator();

            break;
        }


        // --------------------------------------------------------------------
        // View
        // --------------------------------------------------------------------


        include_once('photos.php');
        $photos = new photos();
        $photos->album($params[0]);
        break;


    default:

        include_once('photos.php');
        $photos = new photos();
        $photos->listing();

    }

}

?>