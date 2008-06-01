<?php

/**
* controller for bayes module
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

            $user = !empty($params[0]) ? $params[0]: null;

            // Edit profile registration
            include_once('suxEdit.php');
            $reg = new suxEdit($user);

            if ($reg->formValidate()) {
                $reg->formProcess();
                $reg->formSuccess();
            }
            else $reg->formBuild();

            break;


    default:

        // --------------------------------------------------------------------
        // Default
        // --------------------------------------------------------------------

        echo 'bayes module';
        break;

    }

}

?>