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

    case 'relationship' :

        // --------------------------------------------------------------------
        // Set a relationship with a user
        // --------------------------------------------------------------------

        if (empty($params[0])) suxFunct::redirect(suxFunct::makeUrl('/society'));

        $soc = new societyEdit($params[0]);

        if ($soc->formValidate($_POST)) {
            $soc->formProcess($_POST);
            $soc->formSuccess();
        }
        else {
            $soc->formBuild($_POST);
        }

        break;

    }

}

