<?php

/**
* blogReply
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class blogReply extends component {

    // Module name
    protected $module = 'blog';

    // Form name
    protected $form_name = 'blogReply';

    // Object: suxThreadedMessages()
    protected $msg;

    // Var
    private $parent;


    /**
    * Constructor
    *
    * @param int $parent_id
    */
    function __construct($parent_id) {

        // Declare objects
        $this->msg = new suxThreadedMessages();
        $this->r = new blogRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        $parent = $this->msg->getByID($parent_id);
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
        $msg['parent_id'] = $clean['parent_id'];

        $id = $this->msg->save($_SESSION['users_id'], $msg);

        $this->log->write($_SESSION['users_id'], "sux0r::blogReply()  messages_id: {$id}", 1); // Private

        $tmp = $this->msg->getByID($clean['parent_id']); // Is actually published?
        if ($tmp) {

            // Clear caches
            $this->tpl->clearCache(null, $_SESSION['nickname']);

            // Log message
            $log = '';
            $url = suxFunct::makeUrl("/user/profile/{$_SESSION['nickname']}", null, true);
            $log .= "<a href='$url'>{$_SESSION['nickname']}</a> ";
            $log .= mb_strtolower($this->r->gtext['replied_blog']);
            $url = suxFunct::makeUrl("/blog/view/{$tmp['thread_id']}", null, true);
            $log .= " <a href='$url'>{$tmp['title']}</a>";

            // Log
            $this->log->write($_SESSION['users_id'], $log);

            // Clear cache
            $tpl = new suxTemplate('user');
            $tpl->clearCache('profile.tpl', $_SESSION['nickname']);

        }


    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        $this->tpl->clearCache(null, $_SESSION['nickname']); // Clear cache
        suxFunct::redirect(suxFunct::makeUrl('/blog/view/' . $this->parent['thread_id'])); // Redirect

    }


}


