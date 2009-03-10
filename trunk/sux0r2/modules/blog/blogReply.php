<?php

/**
* blogReply
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
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
        suxValidate::register_object('this', $this); // Register self to validator

        // Objects
        $this->user = new suxuser();
        $this->msg = new suxThreadedMessages();
        $this->nb = new bayesUser();
        $this->link = new suxLink();

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

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

        $this->r->title .= " | {$this->r->gtext['blog']} | {$this->r->gtext['reply']}";

        // Template
        $this->tpl->display('reply.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        $msg['blog'] = true;
        $msg['title'] = $clean['title'];
        $msg['body'] = $clean['body'];

        $id = $this->msg->saveMessage($_SESSION['users_id'], $msg, $clean['parent_id']);

        $this->user->log("sux0r::blogReply()  messages_id: {$id}", $_SESSION['users_id'], 1); // Private

        $tmp = $this->msg->getMessage($clean['parent_id']); // Is actually published?
        if ($tmp) {

            // Clear caches
            $this->tpl->clear_cache(null, $_SESSION['nickname']);

            // Log message
            $log = '';
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['replied_blog']);
            $url = suxFunct::makeUrl("/blog/view/{$tmp['thread_id']}", null, true);
            $log .= " <a href='$url'>{$tmp['title']}</a>";

            // Log
            $this->user->log($log);

            // Clear cache
            $tpl = new suxTemplate('user');
            $tpl->clear_cache('profile.tpl', $_SESSION['nickname']);

        }


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