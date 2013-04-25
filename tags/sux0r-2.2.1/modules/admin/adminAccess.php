<?php

/**
* adminAccess
*
* @author     Dac Chartrand <dac.chartrand@gmail.com>
* @license    http://www.fsf.org/licensing/licenses/gpl-3.0.html
*/

class adminAccess extends component {

    // Module name
    protected $module = 'admin';

    // Form name
    protected $form_name = 'adminAccess';

    // Var:
    private $nickname;

    // Var:
    private $users_id;

    // Var:
    private $root;

    // Var:
    private $banned;


    /**
    * Constructor
    *
    */
    function __construct($nickname) {

        // Declare objects
        $this->r = new adminRenderer($this->module); // Renderer
        suxValidate::register_object('this', $this); // Register self to validator
        parent::__construct(); // Let the parent do the rest

        // Redirect if not logged in
        if (empty($_SESSION['users_id'])) suxFunct::redirect(suxFunct::makeUrl('/user/register'));

        // Security check
        if (!$this->user->isRoot()) suxFunct::redirect(suxFunct::makeUrl('/home'));

        $tmp = $this->user->getByNickname($nickname);
        if (!$tmp) suxFunct::redirect(suxFunct::getPreviousURL()); // Invalid user

        // Declare properties
        $this->nickname = $nickname;
        $this->users_id = $tmp['users_id'];
        $this->root = $tmp['root'];
        $this->banned = $tmp['banned'];

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
        // Pre assign template variables, maybe overwritten by &$dirty
        // --------------------------------------------------------------------

        $this->tpl->assign('root', $this->root);
        $this->tpl->assign('banned', $this->banned);

        // Dynamically create access dropdowns
        $myOptions = array();
        $mySelect = array();
        $tmp = array('0' => '---');
        foreach($GLOBALS['CONFIG']['ACCESS'] as $key => $val) {

            // Manipulate the array into something Smarty can use
            $tmp2 = $tmp;
            $val = array_flip($val);
            foreach ($val as $key2 => $val2) $tmp2[$key2] = $val2;
            $val = $tmp2;

            $myOptions[$key] = $val;
            $mySelect[$key] = $this->user->getAccess($key, $this->users_id);

        }

        if (!empty($dirty)) foreach ($dirty as $key => $val) {
            // Use the submitted values
            if (isset($mySelect[$key])) $mySelect[$key] = $val;
        }

        $this->tpl->assign('myOptions', $myOptions);
        $this->tpl->assign('mySelect', $mySelect);

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

        $this->tpl->assign('access', $GLOBALS['CONFIG']['ACCESS']);
        $this->tpl->assign('nickname', $this->nickname);
        $this->tpl->assign('users_id', $this->users_id);
        $this->r->text['form_url'] = suxFunct::makeUrl("/admin/access/{$this->nickname}");
        $this->r->text['back_url'] = suxFunct::getPreviousURL();
        if ($this->users_id == $_SESSION['users_id']) $this->tpl->assign('disabled', 'disabled="disabled"');

        $this->r->title .= " | {$this->r->gtext['edit_access']}";

        // Display template
        $this->tpl->display('access.tpl');


    }



    /**
    * Process the form
    *
    * @param array $clean reference to validated $_POST
    */
    function formProcess(&$clean) {

        // --------------------------------------------------------------------
        // Delete !!!
        // --------------------------------------------------------------------

        if (isset($clean['delete_user']) && $clean['delete_user'] == 1) {

            // Begin transaction
            $db = suxDB::get();
            $tid = suxDB::requestTransaction();

            try {

                $query = 'DELETE FROM bayes_auth WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM bookmarks WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM link__bookmarks__users WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM link__rss_feeds__users WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM messages WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM messages_history WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM openid_trusted WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM photoalbums WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM photos WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM rss_feeds WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM socialnetwork WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM socialnetwork WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM tags WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM users_access WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM users_info WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM users_log WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM users_openid WHERE users_id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                $query = 'DELETE FROM users WHERE id = ? ';
                $st = $db->prepare($query);
                $st->execute(array($this->users_id));

                // Log, private
                $this->log->write($_SESSION['users_id'], "sux0r::adminAccess() deleted users_id: {$this->users_id} ", 1);

            }
            catch (Exception $e) {

                $db->rollback();
                throw($e); // Hot potato!
            }

            suxDB::commitTransaction($tid); // Commit

            return; // Drop out of this function

        }


        // --------------------------------------------------------------------
        // Resume normal access control
        // --------------------------------------------------------------------

        // Root
        if (isset($clean['root'])) $this->user->root($this->users_id);
        elseif ($this->users_id != $_SESSION['users_id']) {
            // Don't allow a user to unroot themselves
            $this->user->unroot($this->users_id);
        }

        // Banned
        if (!isset($clean['banned'])) $this->user->unban($this->users_id);
        elseif ($this->users_id != $_SESSION['users_id']) {
            // Don't allow a user to ban themselves
            $this->user->ban($this->users_id);
        }

        foreach($GLOBALS['CONFIG']['ACCESS'] as $key => $val) {
            if (isset($clean[$key])) {
                if ($clean[$key]) $this->user->saveAccess($this->users_id, $key, $clean[$key]);
                else $this->user->removeAccess($key, $this->users_id);
            }
        }

        // Log, private
        $this->log->write($_SESSION['users_id'], "sux0r::adminAccess() users_id: {$this->users_id} ", 1);

    }


    /**
    * The form was successfuly processed
    */
    function formSuccess() {

        suxFunct::redirect(suxFunct::getPreviousURL());

    }




}


?>