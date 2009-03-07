<?php

/**
* controller
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

function sux($action, $params = null) {


    include_once('cropper.php');
    $cropper = new cropper();


    if ($cropper->formValidate($_POST)) {
        $cropper->formProcess($_POST);
        $cropper->formSuccess();
    }
    else {
        $cropper->formBuild($action, $params[0], $_POST);
    }






}

?>