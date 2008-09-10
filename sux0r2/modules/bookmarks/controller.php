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
    
    // Alphasort
    $alphasort = false;
    if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'alpha') $alphasort = true;                 

    switch($action)
    {

    case 'approve' :

        // --------------------------------------------------------------------
        // Approve
        // --------------------------------------------------------------------

        break; // TODO


    case 'suggest' :

        // --------------------------------------------------------------------
        // Suggest
        // --------------------------------------------------------------------

        include_once('bookmarksSuggest.php');
        $bm = new bookmarksSuggest();

        if ($bm->formValidate($_POST)) {
            $bm->formProcess($_POST);
            $bm->formSuccess();
        }
        else {
            $bm->formBuild($_POST);
        }

        break;


    case 'edit' :

        // --------------------------------------------------------------------
        // Edit
        // --------------------------------------------------------------------

        $id = !empty($params[0]) ? $params[0]: null;

        include_once('bookmarksEdit.php');
        $edit = new bookmarksEdit($id);

        if ($edit->formValidate($_POST)) {
            $edit->formProcess($_POST);
            $edit->formSuccess();
        }
        else {
            $edit->formBuild($_POST);
        }

        break;


    case 'user' :

        // --------------------------------------------------------------------
        // User
        // --------------------------------------------------------------------
        
        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));
        }        
                
        include_once('bookmarks.php');
        $bm = new bookmarks();                      
        $bm->user($params[0], $alphasort);

        break; 


    case 'tag' :

        // --------------------------------------------------------------------
        // Tags
        // --------------------------------------------------------------------

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/bookmarks'));
        }

        include_once('bookmarks.php');
        $bm = new bookmarks();

        if ($params[0] == 'cloud') $bm->tagcloud();
        else $bm->tag($params[0], $alphasort);        

        break;


    default:

        // --------------------------------------------------------------------
        // Default
        // --------------------------------------------------------------------

        include_once('bookmarks.php');
        $bm = new bookmarks();

        $bm->listing($alphasort);

        break;

    }

}

?>