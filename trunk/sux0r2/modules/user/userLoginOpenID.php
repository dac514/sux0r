<?php

/**
* userLoginOpenID
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.gnu.org/licenses/agpl.html
*/

require_once('userRegisterOpenID.php');

class userLoginOpenID extends userRegisterOpenID {


    /**
    * Constructor
    *
    */
    function __construct() {

        parent::__construct(); // Call userRegisterOpenID

        if ($this->user->loginCheck()) {

            // Redirect to previous page
            if (isset($_SESSION['breadcrumbs'])) foreach($_SESSION['breadcrumbs'] as $val) {
                if (!preg_match('#^user/[login|logout|register|edit]#i', $val)) {
                    suxFunct::redirect(suxFunct::makeUrl($val));
                    break;
                }
            }

            // Nothing of value was found, redirect to user page
            suxFunct::redirect(suxFunct::makeUrl('/user/profile/' . $_SESSION['nickname']));

        }
        else{
            // Too many password failures?
            if ($this->user->maxPasswordFailures()) {
                $this->r->title .= " | {$this->r->gtext['pw_failure']}";
                $this->tpl->display('pw_failure.tpl');
                die();
            }

        }



    }


    /**
    * Override: build the form and show the template
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
            suxValidate::register_validator('url', 'url', 'notEmpty', false, false, 'trim');
            suxValidate::register_validator('url2', 'url', 'isURL');

        }

        // Urls
        $this->r->text['form_url'] = suxFunct::makeUrl('/user/login/openid');
        $this->r->text['back_url'] = suxFunct::getPreviousURL();

        $this->r->title .= " | {$this->r->gtext['openid_login']}";

        // Template
        $this->tpl->display('login_openid.tpl');

    }



}


?>