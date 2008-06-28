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


    case 'edit' :

        // --------------------------------------------------------------------
        // Edit
        // --------------------------------------------------------------------

        $id = !empty($params[0]) ? $params[0]: null;

        include_once('blogEdit.php');
        $reg = new blogEdit($id);

        if ($reg->formValidate($_POST)) {
            $reg->formProcess($_POST);
            // $reg->formSuccess();
        }
        else {
            $reg->formBuild($_POST);
        }

        break;

        // --------------------------------------------------------------------
        // View
        // --------------------------------------------------------------------

    case 'view' :

        if (empty($params[0]) || !filter_var($params[0], FILTER_VALIDATE_INT)) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        echo 'TODO';
        break;


        // --------------------------------------------------------------------
        // Author
        // --------------------------------------------------------------------

    case 'author' :

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blog.php');
        $blog = new blog();
        $blog->author($params[0]);
        break;

        // --------------------------------------------------------------------
        // Category
        // --------------------------------------------------------------------

    case 'category' :

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blog.php');
        $blog = new blog();
        echo 'todo';
        // $blog->category($params[0]);
        break;


    case 'month' :

        $date = !empty($params[0]) ? $params[0]: date('Y-m-d');

        include_once('blog.php');
        $blog = new blog();
        $blog->month($date);
        break;

    default:

        // --------------------------------------------------------------------
        // Default
        // --------------------------------------------------------------------

        include_once('blog.php');
        $blog = new blog();
        $blog->listing();
        break;

    }

}

?>