<?php

/**
* controller for openid module
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

    unset($action, $params); // We don't use these here

    require_once('suxOpenID.php');

    $openID = new suxOpenID();
    //$openID->profile['debug'] = true;

    // Pick a runmode
    $run_mode = (!empty($_REQUEST['openid_mode']) ? $_REQUEST['openid_mode'] : 'no') . '_mode';
    if (!preg_match('/^(\w|\-)+$/', $run_mode)) $run_mode = 'no_mode';

    // Go
    if (method_exists($openID, $run_mode)) $openID->$run_mode();
    else $openID->no_mode();

}

?>