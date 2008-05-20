<?php

/**
* suxLoginOpenID
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

require_once('suxRegisterOpenID.php');

class suxLoginOpenID extends suxRegisterOpenID {


    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @param string $key PDO dsn key
    */
    function __construct($key = null) {

        parent::__construct($key); // Call suxRegisterOpenID

        // If this user is already logged in, redirect to user page
        if ($this->loginCheck()) {
            $url = suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']);
            suxFunct::redirect($url);
        }

    }


    /**
    * Override: build the form and show the template
    */
    function formBuild() {

        if (!empty($_POST)) $this->tpl->assign($_POST);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');

        }

        // Url
        $this->r->text['form_url'] = suxFunct::makeUrl('/user/login/openid');

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $output = $this->tpl->assemble('login_openid.tpl');
        echo $output;

    }



}


?>