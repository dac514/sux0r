<?php

/**
* userReset
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

require_once(dirname(__FILE__) . '/../../includes/suxTemplate.php');
require_once(dirname(__FILE__) . '/../../includes/suxValidate.php');
require_once(dirname(__FILE__) . '/../../includes/suxRenderer.php');

class userReset {

    // Variables
    public $gtext = array();
    private $module = 'user';

    // Objects
    public $tpl;
    public $r;
    protected $user;

    /**
    * Constructor
    *
    */
    function __construct() {

        $this->user = new suxUser(); // User
        $this->tpl = new suxTemplate($this->module); // Template
        $this->r = new suxRenderer($this->module); // Renderer
        $this->tpl->assign_by_ref('r', $this->r); // Renderer referenced in template
        suxValidate::register_object('this', $this); // Register self to validator

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

            // Register our additional criterias
            suxValidate::register_criteria('userExists', 'this->userExists');
            suxValidate::register_criteria('isValidCaptcha', 'this->isValidCaptcha');

            // Register our validators
            // register_validator($id, $field, $criteria, $empty = false, $halt = false, $transform = null, $form = 'default')
            suxValidate::register_validator('user', 'user', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('user2', 'user', 'userExists');
            suxValidate::register_validator('captcha', 'captcha', 'isValidCaptcha');

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/user/reset');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        $this->r->title .= " | {$this->r->gtext['reset']}";

        // Template
        $this->tpl->display('reset.tpl');

    }


    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // Captcha
        unset($_SESSION['captcha']);
        unset($clean['captcha']);

        $user = $this->getByID($clean['user']);
        if (!$user) throw new Exception('Invalid user?!');
        elseif (@$user['banned']) {
            // Banned user, abort
            suxFunct::killSession();
            suxFunct::redirect(suxFunct::makeUrl('/banned'));
        }

        // Array
        $reset_user = array();
        $reset_user['nickname'] = $user['nickname'];
        $reset_user['password'] = $this->user->generatePw();
        $reset_user_id = $user['users_id'];

        // Email
        $subject = "{$GLOBALS['CONFIG']['TITLE']}: {$this->r->gtext['reset_mail_1']} {$reset_user['nickname']}";
        $message = "{$this->r->gtext['reset_mail_2']}:\n\n{$reset_user['password']}\n\n";
        $message .= "{$this->r->gtext['reset_mail_3']}: {$_SERVER['REMOTE_ADDR']}\n\n";
        $message .= "---\n" . suxFunct::makeUrl('/', null, true) . "\n\n";

        // Do the dirty
        $this->user->save($reset_user_id, $reset_user);
        mb_send_mail($user['email'], $subject, $message);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        // Template
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        $this->r->title .= " | {$this->r->gtext['success']}";
        $this->r->bool['edit'] = true; // Generic message

        $this->tpl->display('success.tpl');

    }



    /**
    * check if the user actually exists
    *
    * @return bool
    */
    function userExists($value, $empty, &$params, &$formvars) {

        if (empty($formvars['user'])) return false;
        if (!$this->getByID($formvars['user'])) return false;
        return true;

    }

    /**
    * for suxValidate, check for matching Captcha
    *
    * @return bool
    */
    function isValidCaptcha($value, $empty, &$params, &$formvars) {

        if (empty($formvars['captcha'])) return false;
        if (empty($_SESSION['captcha'])) return false;

        if (mb_strtolower($_SESSION['captcha']) == mb_strtolower($formvars['captcha'])) return true;
        else return false;

    }


    /**
    *
    * @return bool
    */
    private function getUser($user) {

        if (filter_var($user, FILTER_VALIDATE_EMAIL)) {
            return $this->user->getByEmail($user);
        }
        else {
            return $this->user->getByNickname($user);
        }

    }



}


?>
