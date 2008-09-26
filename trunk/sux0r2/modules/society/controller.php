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

    case 'relationship' :

        // --------------------------------------------------------------------
        // Set a relationship with a user
        // --------------------------------------------------------------------

        if (empty($params[0])) suxFunct::redirect(suxFunct::makeUrl('/society'));

        include_once('societyEdit.php');
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

?>