<?php

/**
* userOpenID
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../modules/openid/openid.php');
require_once('userRenderer.php');

class userOpenID  {

    // Variables
    private $nickname;
    private $users_id;
    public $gtext = array();
    private $module = 'user';


    // Objects
    public $tpl;
    public $r;
    private $user;
    private $openid;

    /**
    * Constructor
    *
    */
    function __construct($nickname) {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new userRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check. Is the user allowed to edit this?
        $tmp = $this->user->getUserByNickname($nickname, true);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user
        elseif ($tmp['users_id'] != $_SESSION['users_id']) {
            // Check that the user is allowed to be here
            if (!$this->user->isRoot()) {
                suxFunct::redirect(suxFunct::getPreviousURL());
            }
        }

        // Pre assign variables
        $this->nickname = $nickname;
        $this->users_id = $tmp['users_id'];

        // Openid object
        $this->openid = new openid();

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
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')

            suxValidate::register_validator('integrity', 'integrity:users_id:nickname', 'hasIntegrity');

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


?>