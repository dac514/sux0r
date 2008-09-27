<?php

/**
* blogReply
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
require_once(dirname(__FILE__) . '/../../includes/suxThreadedMessages.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../bayes/bayesUser.php');
require_once('blogRenderer.php');

class blogReply {

    // Variables
    public $gtext = array();
    private $parent;
    private $module = 'blog';

    // Objects
    public $tpl;
    public $r;
    private $user;
    private $msg;
    private $nb;
    private $link;


    /**
    * Constructor
    *
    * @param int $parent_id
    */
    function __construct($parent_id) {

        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new blogRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        $this->gtext = suxFunct::gtext($this->module); // Language
        $this->r->text =& $this->gtext;
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxuser();
        $this->msg = new suxThreadedMessages();
        $this->nb = new bayesUser();
        $this->link = new suxLink();

        // Redirect if not logged in
        $this->user->loginCheck(suxfunct::makeUrl('/user/register'));

        $parent = $this->msg->getMessage($parent_id);
        if (!$parent) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid message, redirect

        $this->parent = $parent;

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

        // --------------------------------------------------------------------
        // Form logic
        // --------------------------------------------------------------------

        if (!empty($dirty)) $this->tpl->assign($dirty);
        else suxValidate::disconnect();

        if (!suxValidate::is_registered_form()) {

            suxValidate::connect($this->tpl, true); // Reset connection

            // Register our validators
            suxValidate::register_validator('integrity', 'integrity:parent_id', 'hasIntegrity');
            suxValidate::register_validator('title', 'title', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('body', 'body', 'notEmpty', false, false, 'trim');

        }

        // Additional variables
        $this->r->text['form_url'] = suxFunct::makeUrl('/blog/reply/' . $this->parent['id']);
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        // Parent
        $this->tpl->assign('parent_id', $this->parent['id']);
        $this->tpl->assign('parent', "{$this->parent['title']} \n\n {$this->parent['body_plaintext']}");

        $this->r->title .= " | {$this->r->text['blog']} | {$this->r->text['reply']}";

        // Template
        $this->tpl->display('reply.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $msg['blog'] = 1;
        $msg['title'] = $clean['title'];
        $msg['body'] = $clean['body'];

        $this->msg->saveMessage($_SESSION['users_id'], $msg, $clean['parent_id']);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        $this->tpl->clear_cache(null, $_SESSION['nickname']); // Clear cache
        suxFunct::redirect(suxFunct::makeUrl('/blog/view/' . $this->parent['thread_id'])); // Redirect

    }


}


?>