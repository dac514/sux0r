<?php

/**
* userOpenID
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class userOpenID extends component {

    // Module name
    protected $module = 'user';

    // Form name
    protected $form_name = 'userOpenID';

    // Var:
    private $nickname;

    // Var:
    private $users_id;

    // Object: openid()
    private $openid;


    /**
    * Constructor
    *
    */
    function __construct($nickname) {

        // Declare objects
        $this->openid = new openid();
        $this->r = new userRenderer($this->module); // Renderer
        (new suxValidate())->register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check. Is the user allowed to edit this?
        $tmp = $this->user->getByNickname($nickname, true);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user
        elseif ($tmp['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot()) {
                suxFunct::redirect(suxFunct::getPreviousURL());
            }
        }

        // Declare properties
        $this->nickname = $nickname;
        $this->users_id = $tmp['users_id'];


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
        else (new suxValidate())->disconnect();

        if (!(new suxValidate())->is_registered_form()) {

            (new suxValidate())->connect($this->tpl, true); // Reset connection

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            (new suxValidate())->register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');

        }

        // --------------------------------------------------------------------
        // Template
        // --------------------------------------------------------------------

        // Additional
        $this->tpl->assign('nickname', $this->nickname);
        $this->tpl->assign('users_id', $this->users_id);
        $this->r->text['form_url'] = suxFunct::makeUrl("/user/openid/{$this->nickname}");
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        $this->r->arr['openids'] = $this->user->getOpenIDs($this->users_id);
        $this->r->arr['trusted'] = $this->openid->getTrusted($this->users_id);
        $this->r->title .= " | {$this->r->gtext['edit_openid']}";

        // Display template
        $this->tpl->display('openid.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // Security check
        if ($clean['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot()) {
                suxFunct::redirect(suxFunct::getPreviousURL());
            }
        }

        // Accounts
        if (isset($clean['detach'])) foreach ($clean['detach'] as $url) {

            if (!$this->user->isRoot()) {
                $tmp = $this->user->getUserByOpenID($url);
                if ($tmp['users_id'] != $_SESSION['users_id'])
                    continue; // Skip
            }

            $this->user->detachOpenID($url);

        }

        // Trusted consumers
        if (isset($clean['detach2'])) foreach ($clean['detach2'] as $id => $url) {

            if (!$this->user->isRoot()) {
                $tmp = $this->openid->checkTrusted($_SESSION['users_id'], $url);
                if (!$tmp)
                    continue; // Skip
            }

            $this->openid->untrustUrl($id);

        }


    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::makeUrl("/user/openid/{$this->nickname}"));

    }



}


