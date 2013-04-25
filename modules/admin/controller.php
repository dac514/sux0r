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


    case 'access' :

        // --------------------------------------------------------------------
        // Access
        // --------------------------------------------------------------------

        if (empty($params[0])) {
            suxFunct::redirect(suxFunct::makeUrl('/admin'));
        }

        $edit = new adminAccess($params[0]);

        if ($edit->formValidate($_POST)) {
            $edit->formProcess($_POST);
            $edit->formSuccess();
        }
        else {
            $edit->formBuild($_POST);
        }

        break;


    case 'log' :

        // --------------------------------------------------------------------
        // Log
        // --------------------------------------------------------------------

        $nickname = null;
        if (!empty($params[0])) $nickname = $params[0];

        $admin = new adminLog($nickname);
        $admin->display();

        break;


    case 'purge' :

        // --------------------------------------------------------------------
        // Purge logs
        // --------------------------------------------------------------------

        $edit = new adminPurge();

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

        $admin = new admin();

        if ($admin->formValidate($_POST)) {
            $admin->formProcess($_POST);
            $admin->formSuccess();
        }
        else {
            $admin->formBuild($_POST);
        }

        break;

    }

}

?>