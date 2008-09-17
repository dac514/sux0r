<?php

/**
* bookmarksApprove
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

require_once(dirname(__FILE__) . '/../../includes/suxBookmarks.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('bookmarksRenderer.php');

class bookmarksApprove  {

    // Variables
    public $gtext = array();
    private $module = 'bookmarks';

    // Objects
    public $tpl;
    public $r;
    protected $user;
    protected $bm;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->bm = new suxBookmarks();
        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new bookmarksRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

    }


    /**
    * Validate the form
    *
    * @param array $dirty reference to unverified $_POST
    * @return bool
    */
    function formValidate(&$dirty) {
        return suxValidate::formValidate($dirty, $this->tpl);
    }



    /**
    * Build the form and show the template
    *
    * @param array $dirty reference to unverified $_POST
    */
    function formBuild(&$dirty) {

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('bookmarks', 'bookmarks', 'isInt', true);

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/bookmarks/approve');
        $this->r->text['back_url'] = suxFunct::getPreviousURL($GLOBALS['CONFIG']['PREV_SKIP']);

        // bookmarks
        $this->r->fp = $this->bm->getUnpublishedBookmarks();

        $this->tpl->display('approve.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        if (isset($clean['bookmarks'])) foreach ($clean['bookmarks'] as $key => $val) {

            if ($val == 1) $this->bm->approveBookmark($key);
            else $this->bm->deleteBookmark($key);

        }

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Redirect
        suxFunct::redirect(suxFunct::getPreviousURL($GLOBALS['CONFIG']['PREV_SKIP']));

    }



}


?>