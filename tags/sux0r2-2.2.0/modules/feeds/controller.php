<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

function sux($action, $params = null) {

    switch($action)
    {

    case 'admin' :

        // --------------------------------------------------------------------
        // Admin
        // --------------------------------------------------------------------

        $admin = new feedsAdmin();

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

        $feeds = new feedsApprove();

        if ($feeds->formValidate($_POST)) {
            $feeds->formProcess($_POST);
            $feeds->formSuccess();
        }
        else {
            $feeds->formBuild($_POST);
        }

        break;


    case 'edit' :

        // --------------------------------------------------------------------
        // Edit
        // --------------------------------------------------------------------

        $id = !empty($params[0]) ? $params[0]: null;

        $edit = new feedsEdit($id);

        if ($edit->formValidate($_POST)) {
            $edit->formProcess($_POST);
            $edit->formSuccess();
        }
        else {
            $edit->formBuild($_POST);
        }

        break;


    case 'suggest' :

        // --------------------------------------------------------------------
        // Suggest
        // --------------------------------------------------------------------

        $feeds = new feedsSuggest();

        if ($feeds->formValidate($_POST)) {
            $feeds->formProcess($_POST);
            $feeds->formSuccess();
        }
        else {
            $feeds->formBuild($_POST);
        }

        break;


    case 'manage' :

        // --------------------------------------------------------------------
        // Manage
        // --------------------------------------------------------------------

        $feeds = new feedsManage();

        if ($feeds->formValidate($_POST)) {
            $feeds->formProcess($_POST);
            $feeds->formSuccess();
        }
        else {
            $feeds->formBuild($_POST);
        }

        break;


    case 'user' :

        // --------------------------------------------------------------------
        // User
        // --------------------------------------------------------------------

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/feeds'));
        }

        $feeds = new feeds();
        $feeds->user($params[0]);

        break;


    case 'purge' :

        // --------------------------------------------------------------------
        // Purge feeds
        // --------------------------------------------------------------------

        $edit = new feedsPurge();

        if ($edit->formValidate($_POST)) {
            $edit->formProcess($_POST);
            $edit->formSuccess();
        }
        else {
            $edit->formBuild($_POST);
        }


        break;


    default:

        // --------------------------------------------------------------------
        // Default
        // --------------------------------------------------------------------

        $feeds = new feeds();

        if (filter_var($action, FILTER_VALIDATE_INT) && $action > 0) $feeds->listing($action);
        else $feeds->listing();

        break;

    }

}

?>