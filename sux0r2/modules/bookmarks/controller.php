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

        $admin = new bookmarksAdmin();

        if ($admin->formValidate($_POST)) {
            $admin->formProcess($_POST);
            $admin->formSuccess();
        }
        else {
            $admin->formBuild();
        }


        break;


    case 'approve' :

        // --------------------------------------------------------------------
        // Approve
        // --------------------------------------------------------------------

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

        $bm = new bookmarks();

        if ($params[0] == 'cloud') $bm->tagcloud();
        else $bm->tag($params[0], $alphasort);

        break;


    case 'rss':

        // --------------------------------------------------------------------
        // RSS
        // --------------------------------------------------------------------

        $bm = new bookmarks();
        $bm->rss();
        break;


    default:

        // --------------------------------------------------------------------
        // Default
        // --------------------------------------------------------------------

        $bm = new bookmarks();
        $bm->listing($alphasort);
        break;

    }

}

