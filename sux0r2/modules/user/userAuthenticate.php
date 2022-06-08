<?php

/**
* userAuthenticate
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class userAuthenticate extends component {

    // Module name
    protected $module = 'user';


    /**
    * Constructor
    *
    */
    function __construct() {

        $this->r = new suxRenderer($this->module); // Renderer
        parent::__construct(); // Let the parent do the rest

    }



    /**
    * Login
    */
    function login() {

        if ($this->user->loginCheck() || !$this->user->loginCheck() && $this->user->authenticate()) {

            $this->log->write($_SESSION['users_id'], "sux0r::userAuthenticate() login [IP: {$_SERVER['REMOTE_ADDR']}]", 1); // Log, private

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
        else {

            // Too many password failures?
            if ($this->user->maxPasswordFailures()) {
                $this->r->title .= " | {$this->r->gtext['pw_failure']}";
                $this->tpl->display('pw_failure.tpl');
                die();
            }

            // Note:
            // Threre's a conflift with the authenticate procedure and header('Location:')
            // The workaround is to echo some spaces and force javascript redirect

            echo str_repeat(' ', 40000);
            suxFunct::redirect(suxFunct::makeUrl('/home'));

        }

    }


    /**
    * Logout
    */
    function logout() {

        // Don't kill session (with password failures, perhaps?) if the
        // user isn't actually logged in.
        if ($this->user->loginCheck()) {
            $this->log->write($_SESSION['users_id'], 'sux0r::userAuthenticate() logout', 1); // Log, private
            suxUser::killSession();
        }

        // Ask browser to clear authentication
        header('HTTP/1.0 401 Unauthorized');
        header('WWW-Authenticate: Invalid');

        $this->r->title .= " | {$this->r->gtext['logout']}";

        // Template
        $this->tpl->display('logout.tpl');

    }


}


