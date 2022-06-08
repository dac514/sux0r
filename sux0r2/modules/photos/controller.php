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

        $photos = new photos();
        $photos->view($params[0]);
        break;


    case 'upload':

        // --------------------------------------------------------------------
        // Upload
        // --------------------------------------------------------------------

        $edit = new photosUpload(@$params[0]);

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

        $photos = new photos();
        $photos->listing($params[0]);

        break;

    case 'rss':

        // --------------------------------------------------------------------
        // RSS
        // --------------------------------------------------------------------

        $photos = new photos();
        $photos->rss();
        break;


    default:

        $photos = new photos();
        $photos->listing();
        break;

    }

}

