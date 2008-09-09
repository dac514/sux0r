<?php

/**
* feedsEdit
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

require_once(dirname(__FILE__) . '/../../includes/suxLink.php');
require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRSS.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once('feedsRenderer.php');

class feedsEdit {

    // Variables
    public $gtext = array();
    private $module = 'feeds';
    private $prev_url_preg = '#^feeds/[edit]/#i';
    private $id;

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $rss;        


    /**
    * Constructor
    *
    * @param int $id message id
    */
    function __construct($id = null) {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new feedsRenderer($this->module); // Renderer
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxUser();
        $this->rss = new suxRSS();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        if (filter_var($id, FILTER_VALIDATE_INT)) {
            // TODO:
            // Verfiy that we are allowed to edit this
            $this->id = $id;
        }


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

        unset($dirty['id']); // Don't allow spoofing        
        $feed = array();

        if ($this->id) {

            // Editing a feed

            $tmp = $this->rss->getFeed($this->id, true);

            $feed['id'] = $tmp['id'];
            $feed['title'] = $tmp['title'];
            $feed['url'] = $tmp['url'];
            $feed['body'] = $tmp['body_html'];
            $feed['draft'] = $tmp['draft'];

        }

        // Assign feed
        // new dBug($feed);
        $this->tpl->assign($feed);

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            if ($this->id) suxValidate::register_validator('integrity', 'integrity:id', 'hasIntegrity');

            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');

            suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');


        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/feeds/edit/' . $this->id);
        $this->r->text['back_url'] = suxFunct::getPreviousURL($this->prev_url_preg);

        // Template
        $this->tpl->assign_by_ref('r', $this->r);
        $this->tpl->display('edit.tpl');

    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // --------------------------------------------------------------------
        // Sanity check
        // --------------------------------------------------------------------

        // Message id, edit mode
        if (isset($clean['id']) && filter_var($clean['id'], FILTER_VALIDATE_INT)) {
            // TODO: Check to see if this user is allowed to modify this feed
            // $clean['id'] = false // on fail
        }

        // --------------------------------------------------------------------
        // Create $feed array
        // --------------------------------------------------------------------

        $feed = array(
                'url' => @$clean['url'],
                'title' => $clean['title'],
                'body' => $clean['body'],
                'draft' => @$clean['draft'],
            );

        // --------------------------------------------------------------------
        // Put $feed in database
        // --------------------------------------------------------------------
        
        /* saveFeed() uses the url as the key and ignores the id, it will also 
        automatically unset the users_id if it's an update */        

        $this->rss->saveFeed($_SESSION['users_id'], $feed);


    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // TODO: Clear caches
        echo 'TODO';

    }


}


?>