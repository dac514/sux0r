<?php

/**
* suxRegisterOpenID
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

require_once(dirname(__FILE__) . '/../../includes/suxUser.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class suxRegister extends suxUser {

    public $gtext = array(); // Language
    public $tpl; // Template
    public $r; // Renderer

    /**
    * Constructor
    *
    * @global string $CONFIG['PARTITION']
    * @global string $CONFIG['LANGUAGE']
    * @param string $key PDO dsn key
    */
    function __construct($key = null) {

        parent::__construct($key); // Call parent
        $this->tpl = new suxTemplate('user', $GLOBALS['CONFIG']['PARTITION']); // Template
        $this->gtext = $this->tpl->getLanguage($GLOBALS['CONFIG']['LANGUAGE']); // Language
        $this->r = new suxRenderer(); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator

    }


    /**
    * Validate the form
    *
    * @return bool
    */
    function formValidate() {

        if(!empty($_POST) && suxValidate::is_registered_form()) {
            // Validate
            suxValidate::connect($this->tpl);
            if(suxValidate::is_valid($_POST)) {
                suxValidate::disconnect();
                return true;
            }
        }
        return false;

    }


    /**
    * Build the form and show the template
    */
    function formBuild() {

        if (!empty($_POST)) $this->tpl->assign($_POST);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our additional criterias
            suxValidate::register_criteria('isDuplicateOpenIDUrl', 'this->isDuplicateOpenIDUrl');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');
            suxValidate::register_validator('url3', 'url', 'isDuplicateOpenIDUrl');

        }

        // Language
        $this->r->text = $this->gtext;

        // Url
        $this->r->text['form_url'] = suxFunct::makeUrl('/user/register/openid');

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('register_openid.tpl');

    }



    /**
    * Redirect to openid module
    */
    function formHandoff() {

        $q = array('openid.mode' => 'login', 'openid_url' => $_POST['url']);
        $url = suxFunct::makeUrl('/openid/register/openid', $q);
        suxFunct::redirect($url);

    }


    /**
    * for suxValidate, check if a duplicate openid url exists
    *
    * @return bool
    */
    function isDuplicateOpenIDUrl($value, $empty, &$params, &$formvars) {

        if (empty($formvars['url'])) return false;

        $st = $this->db->prepare("SELECT COUNT(*) FROM {$this->db_table_openid} WHERE openid_url = ? LIMIT 1 ");
        $st->execute(array(suxFunct::canonicalizeUrl($formvars['url'])));

        if ($st->fetchColumn() > 0) return false; // Duplicate found, fail
        else return true;

    }


}


?>