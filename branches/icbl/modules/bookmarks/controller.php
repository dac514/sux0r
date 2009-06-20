<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function sux($action, $params = null) {

    // Alphasort
    $alphasort = false;
    if (isset($_REQUEST['sort']) && $_REQUEST['sort'] == 'alpha') $alphasort = true;

    switch($action)
    {


    case 'admin' :

        // --------------------------------------------------------------------
        // Admin
        // --------------------------------------------------------------------

        include_once('bookmarksAdmin.php');
        $admin = new bookmarksAdmin();

        if ($admin->formValidate($_POST)) {
            $admin->formProcess($_POST);
            $admin->formSuccess();
        }
        else {
            $admin->formBuild($_POST);
        }


        break;


    case 'approve' :

        // --------------------------------------------------------------------
        // Approve
        // --------------------------------------------------------------------

        include_once('bookmarksApprove.php');
        $bm = new bookmarksApprove();

        if ($bm->formValidate($_POST)) {
            $bm->formProcess($_POST);
            $bm->formSuccess();
        }
        else {
            $bm->formBuild($_POST);
        }

        break;


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


    case 'rss':

        // --------------------------------------------------------------------
        // RSS
        // --------------------------------------------------------------------

        include_once('bookmarks.php');
        $bm = new bookmarks();
        $bm->rss();
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