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
        $edit = new blogEdit($id);

        if ($edit->formValidate($_POST)) {
            $edit->formProcess($_POST);
            $edit->formSuccess();
        }
        else {
            $edit->formBuild($_POST);
        }

        break;


    case 'bookmarks' :

        // --------------------------------------------------------------------
        // Scan for bookmarks
        // --------------------------------------------------------------------

        if (empty($params[0]) || !filter_var($params[0], FILTER_VALIDATE_INT)) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blogBookmarks.php');
        $bm = new blogBookmarks($params[0]);

        if ($bm->formValidate($_POST)) {
            $bm->formProcess($_POST);
            $bm->formSuccess();
        }
        else {
            $bm->formBuild($_POST);
        }

        break;


    case 'reply' :

        // --------------------------------------------------------------------
        // Reply
        // --------------------------------------------------------------------

        if (empty($params[0]) || !filter_var($params[0], FILTER_VALIDATE_INT)) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blogReply.php');
        $reply = new blogReply($params[0]);

        if ($reply->formValidate($_POST)) {
            $reply->formProcess($_POST);
            $reply->formSuccess();
        }
        else {
            $reply->formBuild($_POST);
        }

        break;


    case 'view' :

        // --------------------------------------------------------------------
        // View
        // --------------------------------------------------------------------

        if (empty($params[0]) || !filter_var($params[0], FILTER_VALIDATE_INT)) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blog.php');
        $blog = new blog();
        $blog->view($params[0]);
        break;


    case 'author' :

        // --------------------------------------------------------------------
        // Author
        // --------------------------------------------------------------------

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blog.php');
        $blog = new blog();
        $blog->author($params[0]);
        break;


    case 'category' :

        // --------------------------------------------------------------------
        // Category
        // --------------------------------------------------------------------

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        include_once('blog.php');
        $blog = new blog();
        $blog->category($params[0]);
        break;


    case 'month' :

        // --------------------------------------------------------------------
        // Month
        // --------------------------------------------------------------------

        $date = !empty($params[0]) ? $params[0]: date('Y-m-d');

        include_once('blog.php');
        $blog = new blog();
        $blog->month($date);
        break;


    case 'filter' :

        // --------------------------------------------------------------------
        // Filter
        // --------------------------------------------------------------------

        if (empty($_REQUEST['category_id']) || !isset($_REQUEST['threshold'])) {
            suxFunct::redirect(suxFunct::makeUrl('/blog'));
        }

        $cat_id = $_REQUEST['category_id'];
        $threshold = $_REQUEST['threshold'];

        include_once('blog.php');
        $blog = new blog();
        $blog->filter($cat_id, $threshold);
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